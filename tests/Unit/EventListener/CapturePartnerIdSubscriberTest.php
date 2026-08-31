<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusPartnerAdsPlugin\EventListener\CapturePartnerIdSubscriber;
use Setono\SyliusPartnerAdsPlugin\PartnerIdStorage\PartnerIdStorageInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class CapturePartnerIdSubscriberTest extends TestCase
{
    use ProphecyTrait;

    private const PARAM = 'param';

    #[Test]
    public function it_subscribes_to_the_kernel_request_event(): void
    {
        self::assertSame(
            [KernelEvents::REQUEST => ['capture']],
            CapturePartnerIdSubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function it_does_nothing_when_not_the_main_request(): void
    {
        $storage = $this->prophesize(PartnerIdStorageInterface::class);
        $storage->store(Argument::any())->shouldNotBeCalled();

        $request = new Request([self::PARAM => '123']);

        $this->createSubscriber($storage->reveal())->capture($this->createEvent($request, HttpKernelInterface::SUB_REQUEST));
    }

    #[Test]
    public function it_does_nothing_for_xml_http_requests(): void
    {
        $storage = $this->prophesize(PartnerIdStorageInterface::class);
        $storage->store(Argument::any())->shouldNotBeCalled();

        $request = new Request([self::PARAM => '123']);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');

        $this->createSubscriber($storage->reveal())->capture($this->createEvent($request));
    }

    #[Test]
    public function it_does_nothing_when_the_query_parameter_is_not_set(): void
    {
        $storage = $this->prophesize(PartnerIdStorageInterface::class);
        $storage->store(Argument::any())->shouldNotBeCalled();

        $this->createSubscriber($storage->reveal())->capture($this->createEvent(new Request()));
    }

    #[Test]
    #[DataProvider('invalidPartnerIds')]
    public function it_does_nothing_when_the_query_parameter_is_not_a_valid_partner_id(mixed $value): void
    {
        $storage = $this->prophesize(PartnerIdStorageInterface::class);
        $storage->store(Argument::any())->shouldNotBeCalled();

        // must not throw either - a malformed affiliate link must never break a shop page
        $this->createSubscriber($storage->reveal())->capture($this->createEvent(new Request([self::PARAM => $value])));
    }

    /**
     * @return iterable<string, array{mixed}>
     */
    public static function invalidPartnerIds(): iterable
    {
        yield 'empty' => [''];
        yield 'garbage' => ['junk'];
        yield 'zero' => ['0'];
        yield 'negative' => ['-5'];
        yield 'decimal' => ['1.5'];
        yield 'array' => [['1']];
    }

    #[Test]
    public function it_stores_the_partner_id(): void
    {
        $storage = $this->prophesize(PartnerIdStorageInterface::class);
        $storage->store(123)->shouldBeCalled();

        $this->createSubscriber($storage->reveal())->capture($this->createEvent(new Request([self::PARAM => '123'])));
    }

    private function createSubscriber(PartnerIdStorageInterface $storage): CapturePartnerIdSubscriber
    {
        return new CapturePartnerIdSubscriber($storage, self::PARAM);
    }

    private function createEvent(Request $request, int $requestType = HttpKernelInterface::MAIN_REQUEST): RequestEvent
    {
        return new RequestEvent($this->prophesize(HttpKernelInterface::class)->reveal(), $request, $requestType);
    }
}
