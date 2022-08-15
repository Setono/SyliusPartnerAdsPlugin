<?php

declare(strict_types=1);

namespace spec\Setono\SyliusPartnerAdsPlugin\EventListener;

use PhpSpec\ObjectBehavior;
use Prophecy\Argument;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculatorInterface;
use Setono\SyliusPartnerAdsPlugin\Context\ProgramContextInterface;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\EventListener\NotifySubscriber;
use Setono\SyliusPartnerAdsPlugin\Model\ProgramInterface;
use stdClass;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Order\Repository\OrderRepositoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\SessionBagInterface;
use Symfony\Component\HttpFoundation\Session\SessionInterface;
use Symfony\Component\HttpKernel\Event\RequestEvent;
use Symfony\Component\Messenger\Envelope;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Messenger\Stamp\StampInterface;

class NotifySubscriberSpec extends ObjectBehavior
{
    public function let(
        MessageBusInterface $messageBus,
        CookieHandlerInterface $cookieHandler,
        OrderTotalCalculatorInterface $orderTotalCalculator,
        ProgramContextInterface $programContext,
        OrderRepositoryInterface $orderRepository,
    ): void {
        $this->beConstructedWith($messageBus, $cookieHandler, $orderTotalCalculator, $programContext, $orderRepository);
    }

    public function it_is_initializable(): void
    {
        $this->shouldHaveType(NotifySubscriber::class);
    }

    public function it_implements_event_subscriber_interface(): void
    {
        $this->shouldImplement(EventSubscriberInterface::class);
    }

    public function it_notifies(
        RequestEvent $event,
        OrderInterface $order,
        CookieHandlerInterface $cookieHandler,
        OrderTotalCalculatorInterface $orderTotalCalculator,
        ProgramContextInterface $programContext,
        ProgramInterface $program,
        MessageBusInterface $messageBus,
        StampInterface $stamp,
        OrderRepositoryInterface $orderRepository,
    ): void {
        $request = self::getRequest();
        $event->getRequest()->willReturn($request);
        $event->isMainRequest()->willReturn(true);

        $orderRepository->find(123)->willReturn($order);

        $cookieHandler->has($request)->willReturn(true);
        $cookieHandler->get($request)->willReturn(123);
        $programContext->getProgram()->willReturn($program);
        $program->getProgramId()->willReturn(1234);
        $orderTotalCalculator->get($order)->willReturn(199.0);
        $envelope = new Envelope(new stdClass(), [$stamp->getWrappedObject()]);

        $messageBus->dispatch(Argument::any())->willReturn($envelope)->shouldBeCalled();

        $this->notify($event);
    }

    private static function getRequest(): Request
    {
        $session = new class() implements SessionInterface {
            public function start()
            {
            }

            public function getId()
            {
            }

            public function setId($id)
            {
            }

            public function getName()
            {
            }

            public function setName($name)
            {
            }

            public function invalidate($lifetime = null)
            {
            }

            public function migrate($destroy = false, $lifetime = null)
            {
            }

            public function save()
            {
            }

            public function has($name)
            {
            }

            public function get($name, $default = null)
            {
                return 123;
            }

            public function set($name, $value)
            {
            }

            public function all()
            {
            }

            public function replace(array $attributes)
            {
            }

            public function remove($name)
            {
            }

            public function clear()
            {
            }

            public function isStarted()
            {
            }

            public function registerBag(SessionBagInterface $bag)
            {
            }

            public function getBag($name)
            {
            }

            public function getMetadataBag()
            {
            }
        };

        return new class($session) extends Request {
            public function __construct(SessionInterface $session)
            {
                parent::__construct();

                $this->attributes->set('_route', 'sylius_shop_order_thank_you');
                $this->setSession($session);
            }
        };
    }
}
