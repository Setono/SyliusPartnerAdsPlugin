<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\EventListener;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\EventListener\SetCookieSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\HttpKernel\KernelEvents;

final class SetCookieSubscriberTest extends TestCase
{
    use ProphecyTrait;

    private const PARAM = 'param';

    #[Test]
    public function it_subscribes_to_the_kernel_response_event(): void
    {
        self::assertSame(
            [KernelEvents::RESPONSE => ['setCookie']],
            SetCookieSubscriber::getSubscribedEvents(),
        );
    }

    #[Test]
    public function it_does_nothing_when_not_the_main_request(): void
    {
        $cookieHandler = $this->prophesize(CookieHandlerInterface::class);

        $request = new Request([self::PARAM => '123']);
        $event = $this->createEvent($request, HttpKernelInterface::SUB_REQUEST);

        $cookieHandler->set(Argument::cetera())->shouldNotBeCalled();

        $this->createSubscriber($cookieHandler->reveal())->setCookie($event);
    }

    #[Test]
    public function it_does_nothing_for_xml_http_requests(): void
    {
        $cookieHandler = $this->prophesize(CookieHandlerInterface::class);

        $request = new Request([self::PARAM => '123']);
        $request->headers->set('X-Requested-With', 'XMLHttpRequest');
        $event = $this->createEvent($request, HttpKernelInterface::MAIN_REQUEST);

        $cookieHandler->set(Argument::cetera())->shouldNotBeCalled();

        $this->createSubscriber($cookieHandler->reveal())->setCookie($event);
    }

    #[Test]
    public function it_does_nothing_when_the_query_parameter_is_not_set(): void
    {
        $cookieHandler = $this->prophesize(CookieHandlerInterface::class);

        $request = new Request();
        $event = $this->createEvent($request, HttpKernelInterface::MAIN_REQUEST);

        $cookieHandler->set(Argument::cetera())->shouldNotBeCalled();

        $this->createSubscriber($cookieHandler->reveal())->setCookie($event);
    }

    #[Test]
    public function it_sets_the_cookie(): void
    {
        $cookieHandler = $this->prophesize(CookieHandlerInterface::class);

        $request = new Request([self::PARAM => '123']);
        $response = new Response();
        $event = $this->createEvent($request, HttpKernelInterface::MAIN_REQUEST, $response);

        $cookieHandler->set($response, 123)->shouldBeCalled();

        $this->createSubscriber($cookieHandler->reveal())->setCookie($event);
    }

    private function createSubscriber(CookieHandlerInterface $cookieHandler): SetCookieSubscriber
    {
        return new SetCookieSubscriber($cookieHandler, self::PARAM);
    }

    private function createEvent(Request $request, int $requestType, ?Response $response = null): ResponseEvent
    {
        return new ResponseEvent(
            $this->prophesize(HttpKernelInterface::class)->reveal(),
            $request,
            $requestType,
            $response ?? new Response(),
        );
    }
}
