<?php

declare(strict_types=1);

namespace spec\Setono\SyliusPartnerAdsPlugin\EventListener;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\EventListener\SetCookieSubscriber;
use Symfony\Component\HttpFoundation\ParameterBag;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\HttpKernel\Event\ResponseEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;

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

    public function it_does_not_do_anything_when_not_master_request(
        HttpKernelInterface $kernel,
        Request $request,
        Response $response,
    ): void
    {
        $event = new ResponseEvent($kernel->getWrappedObject(), $request->getWrappedObject(), HttpKernelInterface::SUB_REQUEST, $response->getWrappedObject());

        $request->isXmlHttpRequest()->shouldNotBeCalled();

        $this->setCookie($event);
    }

    public function it_does_not_do_anything_when_xml_request(
        HttpKernelInterface $kernel,
        Request $request,
        Response $response,
        CookieHandlerInterface $cookieHandler,
    ): void {
        $event = new ResponseEvent($kernel->getWrappedObject(), $request->getWrappedObject(), HttpKernelInterface::MAIN_REQUEST, $response->getWrappedObject());
        $request->isXmlHttpRequest()->willReturn(true);

        $cookieHandler->set(Argument::any(), Argument::any())->shouldNotBeCalled();

        $this->setCookie($event);
    }

    public function it_does_not_do_anything_when_query_param_is_not_set(
        HttpKernelInterface $kernel,
        Request $request,
        Response $response,
        CookieHandlerInterface $cookieHandler,
        ParameterBag $query,
    ): void {
        $event = new ResponseEvent($kernel->getWrappedObject(), $request->getWrappedObject(), HttpKernelInterface::MAIN_REQUEST, $response->getWrappedObject());
        $request->isXmlHttpRequest()->willReturn(false);
        $query->has($this->param)->willReturn(false);
        $request->query = $query;

        $cookieHandler->set(Argument::any(), Argument::any())->shouldNotBeCalled();

        $this->setCookie($event);
    }

    public function it_sets(
        HttpKernelInterface $kernel,
        Request $request,
        Response $response,
        CookieHandlerInterface $cookieHandler,
        ParameterBag $query,
    ): void {
        $event = new ResponseEvent($kernel->getWrappedObject(), $request->getWrappedObject(), HttpKernelInterface::MAIN_REQUEST, $response->getWrappedObject());
        $request->isXmlHttpRequest()->willReturn(false);
        $query->has($this->param)->willReturn(true);
        $query->get($this->param)->willReturn('123');
        $request->query = $query;

        $cookieHandler->set(Argument::any(), '123')->shouldBeCalled();

        $this->setCookie($event);
    }
}
