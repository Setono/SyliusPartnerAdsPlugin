<?php

declare(strict_types=1);

namespace spec\Setono\SyliusPartnerAdsPlugin\EventListener;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\EventListener\SetCookieSubscriber;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\FilterResponseEvent;

class SetCookieSubscriberSpec extends ObjectBehavior
{
    private $param = 'param';

    public function let(CookieHandlerInterface $cookieHandler): void
    {
        $this->beConstructedWith($cookieHandler, $this->param);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(SetCookieSubscriber::class);
    }

    public function it_does_not_do_anything_when_not_master_request(FilterResponseEvent $event): void
    {
        $event->isMasterRequest()->willReturn(false);
        $event->getRequest()->shouldNotBeCalled();

        $this->setCookie($event);
    }

    public function it_does_not_do_anything_when_xml_request(FilterResponseEvent $event, Request $request, CookieHandlerInterface $cookieHandler): void
    {
        $request->isXmlHttpRequest()->willReturn(true);

        $event->isMasterRequest()->willReturn(true);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $cookieHandler->set(Argument::any(), Argument::any())->shouldNotBeCalled();

        $this->setCookie($event);
    }

    public function it_does_not_do_anything_when_query_param_is_not_set(FilterResponseEvent $event, CookieHandlerInterface $cookieHandler): void
    {
        $request = new Request();

        $event->isMasterRequest()->willReturn(true);
        $event->getRequest()->willReturn($request)->shouldBeCalled();

        $cookieHandler->set(Argument::any(), Argument::any())->shouldNotBeCalled();

        $this->setCookie($event);
    }

    public function it_sets(FilterResponseEvent $event, CookieHandlerInterface $cookieHandler): void
    {
        $request = new Request([
            $this->param => '123',
        ]);

        $event->isMasterRequest()->willReturn(true);
        $event->getRequest()->willReturn($request)->shouldBeCalled();
        $event->getResponse()->willReturn(new Response());

        $cookieHandler->set(Argument::any(), '123')->shouldBeCalled();

        $this->setCookie($event);
    }
}
