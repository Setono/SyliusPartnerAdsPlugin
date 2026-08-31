<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Command;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Client\ClientInterface;
use Setono\SyliusPartnerAdsPlugin\Enum\NotifyWhen;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ProgramRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Lock\LockFactory;

/**
 * Sends pending conversions to Partner Ads. This command is the single place that talks to Partner Ads, and it is
 * therefore also the place that guarantees "at most one notification per order" (see CreateConversionSubscriber for
 * why that is not guaranteed by the database).
 */
#[AsCommand(
    name: 'setono:sylius-partner-ads:process-conversions',
    description: 'Notifies Partner Ads about pending conversions whose orders are eligible',
)]
final class ProcessConversionsCommand extends Command
{
    use ORMTrait;

    /**
     * Two overlapping runs (e.g. a slow run and the next cron tick) would both read the same pending
     * conversions and notify Partner Ads twice about the same order, so only one run may be active at a time.
     */
    public const LOCK_RESOURCE = 'setono_sylius_partner_ads_process_conversions';

    /**
     * How long a run may hold the lock, for lock stores that support expiration. Generous on purpose: a run is
     * bounded by --limit times the HTTP client timeout, and a lock that expires mid-run would defeat its purpose.
     */
    private const LOCK_TTL = 3600.0;

    public function __construct(
        private readonly ConversionRepositoryInterface $conversionRepository,
        private readonly ProgramRepositoryInterface $programRepository,
        private readonly ClientInterface $client,
        private readonly OrderTotalCalculatorInterface $orderTotalCalculator,
        ManagerRegistry $managerRegistry,
        private readonly LockFactory $lockFactory,
        private readonly NotifyWhen $notifyWhen,
    ) {
        $this->managerRegistry = $managerRegistry;

        parent::__construct();
    }

    protected function configure(): void
    {
        $this
            ->addOption('limit', null, InputOption::VALUE_REQUIRED, 'The maximum number of conversions to process in one run', '100')
            ->addOption('max-tries', null, InputOption::VALUE_REQUIRED, 'Mark a conversion as failed when it has been tried unsuccessfully this many times', '10')
        ;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $limit = self::getPositiveIntegerOption($input, 'limit');
        $maxTries = self::getPositiveIntegerOption($input, 'max-tries');

        // The lock is released explicitly in the finally block below. Auto release (which relies on the
        // destructor of the lock object) is disabled so that the release is deterministic and observable.
        $lock = $this->lockFactory->createLock(self::LOCK_RESOURCE, self::LOCK_TTL, autoRelease: false);
        if (!$lock->acquire()) {
            $io->warning('Another instance of this command is already running - exiting');

            return Command::SUCCESS;
        }

        try {
            return $this->process($limit, $maxTries, $io);
        } finally {
            $lock->release();
        }
    }

    private function process(int $limit, int $maxTries, SymfonyStyle $io): int
    {
        $conversions = $this->conversionRepository->findPending($limit, $this->notifyWhen);

        if ([] === $conversions) {
            $io->success('No pending conversions to process');

            return Command::SUCCESS;
        }

        $notified = 0;
        $skipped = 0;
        $failed = 0;

        foreach ($conversions as $conversion) {
            // More than one conversion can exist for the same order (see CreateConversionSubscriber for why).
            // The first one to be notified wins; any other conversion for that order is skipped, never sent.
            if ($this->isAlreadyNotified($conversion)) {
                $conversion->setState(ConversionInterface::STATE_SKIPPED);

                ++$skipped;

                $this->getManager($conversion)->flush();

                continue;
            }

            try {
                $this->notify($conversion);

                $conversion->setState(ConversionInterface::STATE_NOTIFIED);
                $conversion->setNotifiedAt(new \DateTimeImmutable());
                $conversion->setLastError(null);

                ++$notified;
            } catch (\Throwable $e) {
                $conversion->incrementTries();
                $conversion->setLastError($e->getMessage());

                if ($conversion->getTries() >= $maxTries) {
                    $conversion->setState(ConversionInterface::STATE_FAILED);
                }

                ++$failed;

                $io->error(sprintf('Conversion %d failed: %s', (int) $conversion->getId(), $e->getMessage()));
            }

            // flush after each conversion so that a crash mid-run cannot lose a notification we already sent
            $this->getManager($conversion)->flush();
        }

        $io->success(sprintf(
            'Processed %d conversion(s): %d notified, %d skipped, %d failed',
            count($conversions),
            $notified,
            $skipped,
            $failed,
        ));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
    }

    private static function getPositiveIntegerOption(InputInterface $input, string $name): int
    {
        $value = filter_var($input->getOption($name), \FILTER_VALIDATE_INT, ['options' => ['min_range' => 1]]);
        if (false === $value) {
            throw new \InvalidArgumentException(sprintf('The --%s option must be an integer of at least 1', $name));
        }

        return $value;
    }

    private function isAlreadyNotified(ConversionInterface $conversion): bool
    {
        $order = $conversion->getOrder();

        return null !== $order && $this->conversionRepository->hasNotifiedConversionForOrder($order);
    }

    private function notify(ConversionInterface $conversion): void
    {
        $order = $conversion->getOrder();
        if (null === $order) {
            throw new \RuntimeException('The conversion does not have an order associated');
        }

        $channel = $order->getChannel();
        if (null === $channel) {
            throw new \RuntimeException('The order does not have a channel associated');
        }

        $program = $this->programRepository->findOneByChannel($channel);
        if (null === $program || null === $program->getProgramId()) {
            throw new \RuntimeException(sprintf(
                'No enabled program with a program id exists for the channel "%s"',
                (string) $channel->getCode(),
            ));
        }

        $partnerId = $conversion->getPartnerId();
        if (null === $partnerId || $partnerId <= 0) {
            throw new \RuntimeException('The conversion does not have a valid partner id');
        }

        $this->client->notify(
            $program->getProgramId(),
            (string) $order->getNumber(),
            $this->orderTotalCalculator->get($order),
            $partnerId,
            (string) $order->getCustomerIp(),
        );
    }
}
