<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Functional;

use Doctrine\DBAL\Exception as DBALException;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\Attributes\Test;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use Setono\SyliusPartnerAdsPlugin\Tests\Application\Kernel;
use Sylius\Component\Core\Model\ChannelInterface;
use Sylius\Component\Currency\Model\CurrencyInterface;
use Sylius\Component\Locale\Model\LocaleInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

/**
 * The UniqueEntity constraints need a real database, which the functional-tests CI job provides. Without a reachable
 * database the test is skipped. Every test runs inside a transaction that is rolled back afterwards (DAMA).
 */
final class ProgramValidationTest extends KernelTestCase
{
    private const VALIDATION_GROUPS = ['setono_sylius_partner_ads'];

    private EntityManagerInterface $entityManager;

    private ValidatorInterface $validator;

    protected static function getKernelClass(): string
    {
        return Kernel::class;
    }

    protected function setUp(): void
    {
        self::bootKernel();

        $entityManager = self::getContainer()->get('doctrine.orm.entity_manager');
        \assert($entityManager instanceof EntityManagerInterface);

        try {
            $entityManager->getConnection()->executeQuery('SELECT 1');
        } catch (DBALException $e) {
            self::markTestSkipped(sprintf('No database available: %s', $e->getMessage()));
        }

        $this->entityManager = $entityManager;

        $validator = self::getContainer()->get('validator');
        \assert($validator instanceof ValidatorInterface);
        $this->validator = $validator;
    }

    protected function tearDown(): void
    {
        parent::tearDown();

        // see AdminRoutingTest
        restore_exception_handler();
    }

    #[Test]
    public function it_rejects_a_second_program_for_the_same_channel(): void
    {
        $channel = $this->createChannel('FASHION_WEB');
        $this->createProgram(1000, $channel);
        $this->entityManager->flush();

        $violations = $this->validator->validate($this->createProgram(2000, $channel), null, self::VALIDATION_GROUPS);

        self::assertCount(1, $violations);
        self::assertSame('channel', $violations->get(0)->getPropertyPath());
        self::assertSame(
            'A program already exists for this channel. Edit that program instead.',
            (string) $violations->get(0)->getMessage(),
        );
    }

    #[Test]
    public function it_accepts_a_program_for_another_channel(): void
    {
        $this->createProgram(1000, $this->createChannel('FASHION_WEB'));
        $this->entityManager->flush();

        $violations = $this->validator->validate(
            $this->createProgram(2000, $this->createChannel('HOME_WEB')),
            null,
            self::VALIDATION_GROUPS,
        );

        self::assertCount(0, $violations);
    }

    #[Test]
    public function it_rejects_a_duplicate_program_id(): void
    {
        $this->createProgram(1000, $this->createChannel('FASHION_WEB'));
        $this->entityManager->flush();

        $violations = $this->validator->validate(
            $this->createProgram(1000, $this->createChannel('HOME_WEB')),
            null,
            self::VALIDATION_GROUPS,
        );

        self::assertCount(1, $violations);
        self::assertSame('programId', $violations->get(0)->getPropertyPath());
    }

    private function createProgram(int $programId, ChannelInterface $channel): ProgramInterface
    {
        $program = $this->createResource('setono_sylius_partner_ads.factory.program', ProgramInterface::class);
        $program->setProgramId($programId);
        $program->setChannel($channel);
        $program->setEnabled(true);

        $this->entityManager->persist($program);

        return $program;
    }

    private function createChannel(string $code): ChannelInterface
    {
        $currency = $this->createResource('sylius.factory.currency', CurrencyInterface::class);
        $currency->setCode('DKK');
        $this->entityManager->persist($currency);

        $locale = $this->createResource('sylius.factory.locale', LocaleInterface::class);
        $locale->setCode('en_US');
        $this->entityManager->persist($locale);

        $channel = $this->createResource('sylius.factory.channel', ChannelInterface::class);
        $channel->setCode($code);
        $channel->setName($code);
        $channel->setTaxCalculationStrategy('order_items_based');
        $channel->setBaseCurrency($currency);
        $channel->setDefaultLocale($locale);

        $this->entityManager->persist($channel);

        return $channel;
    }

    /**
     * @template T of object
     *
     * @param class-string<T> $class
     *
     * @return T
     */
    private function createResource(string $factoryId, string $class): object
    {
        $factory = self::getContainer()->get($factoryId);
        \assert($factory instanceof FactoryInterface);

        $resource = $factory->createNew();
        \assert($resource instanceof $class);

        return $resource;
    }
}
