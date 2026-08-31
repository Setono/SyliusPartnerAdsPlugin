<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Command;

use Doctrine\Persistence\ManagerRegistry;
use Setono\Doctrine\ORMTrait;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Client\ClientInterface;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\NotifyWhen;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ProgramRepositoryInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'setono:sylius-partner-ads:process-conversions',
    description: 'Notifies Partner Ads about pending conversions whose orders are eligible',
)]
final class ProcessConversionsCommand extends Command
{
    use ORMTrait;

    public function __construct(
        private readonly ConversionRepositoryInterface $conversionRepository,
        private readonly ProgramRepositoryInterface $programRepository,
        private readonly ClientInterface $client,
        private readonly OrderTotalCalculatorInterface $orderTotalCalculator,
        ManagerRegistry $managerRegistry,
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

        $limit = max(1, (int) $input->getOption('limit'));
        $maxTries = max(1, (int) $input->getOption('max-tries'));

        $conversions = $this->conversionRepository->findPending($limit, $this->notifyWhen);

        if ([] === $conversions) {
            $io->success('No pending conversions to process');

            return Command::SUCCESS;
        }

        $notified = 0;
        $failed = 0;

        foreach ($conversions as $conversion) {
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

            $this->getManager($conversion)->flush();
        }

        $io->success(sprintf('Processed %d conversion(s): %d notified, %d failed', count($conversions), $notified, $failed));

        return $failed > 0 ? Command::FAILURE : Command::SUCCESS;
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
