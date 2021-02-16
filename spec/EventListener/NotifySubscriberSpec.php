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
        OrderRepositoryInterface $orderRepository
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
        OrderRepositoryInterface $orderRepository
    ): void {
        $request = self::getRequest();
        $event->getRequest()->willReturn($request);
        $event->isMasterRequest()->willReturn(true);

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
                // TODO: Implement start() method.
            }

            public function getId()
            {
                // TODO: Implement getId() method.
            }

            public function setId($id)
            {
                // TODO: Implement setId() method.
            }

            public function getName()
            {
                // TODO: Implement getName() method.
            }

            public function setName($name)
            {
                // TODO: Implement setName() method.
            }

            public function invalidate($lifetime = null)
            {
                // TODO: Implement invalidate() method.
            }

            public function migrate($destroy = false, $lifetime = null)
            {
                // TODO: Implement migrate() method.
            }

            public function save()
            {
                // TODO: Implement save() method.
            }

            public function has($name)
            {
                // TODO: Implement has() method.
            }

            public function get($name, $default = null)
            {
                return 123;
            }

            public function set($name, $value)
            {
                // TODO: Implement set() method.
            }

            public function all()
            {
                // TODO: Implement all() method.
            }

            public function replace(array $attributes)
            {
                // TODO: Implement replace() method.
            }

            public function remove($name)
            {
                // TODO: Implement remove() method.
            }

            public function clear()
            {
                // TODO: Implement clear() method.
            }

            public function isStarted()
            {
                // TODO: Implement isStarted() method.
            }

            public function registerBag(SessionBagInterface $bag)
            {
                // TODO: Implement registerBag() method.
            }

            public function getBag($name)
            {
                // TODO: Implement getBag() method.
            }

            public function getMetadataBag()
            {
                // TODO: Implement getMetadataBag() method.
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
