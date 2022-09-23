<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\EventSubscriber;

use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\MainRequestTrait\MainRequestTrait;
use Setono\SyliusPartnerAdsPlugin\Context\ProgramContextInterface;
use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\Factory\AffiliateOrderFactoryInterface;
use Sylius\Bundle\ResourceBundle\Event\ResourceControllerEvent;
use Sylius\Component\Core\Model\OrderInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

final class CreateAffiliateOrderSubscriber implements EventSubscriberInterface
{
    use MainRequestTrait;

    use ORMManagerTrait;

    private CookieHandlerInterface $cookieHandler;

    private AffiliateOrderFactoryInterface $affiliateOrderFactory;

    private ProgramContextInterface $programContext;

    private RequestStack $requestStack;

    public function __construct(
        CookieHandlerInterface $cookieHandler,
        AffiliateOrderFactoryInterface $affiliateOrderFactory,
        ManagerRegistry $managerRegistry,
        ProgramContextInterface $programContext,
        RequestStack $requestStack
    ) {
        $this->cookieHandler = $cookieHandler;
        $this->affiliateOrderFactory = $affiliateOrderFactory;
        $this->managerRegistry = $managerRegistry;
        $this->programContext = $programContext;
        $this->requestStack = $requestStack;
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.order.pre_complete' => 'create',
        ];
    }

    public function create(ResourceControllerEvent $event): void
    {
        $request = $this->getMainRequestFromRequestStack($this->requestStack);
        if (null === $request) {
            return;
        }

        if (!$this->cookieHandler->isset($request)) {
            return;
        }

        $program = $this->programContext->getProgram();
        if (null === $program) {
            return;
        }

        /** @var OrderInterface $order */
        $order = $event->getSubject();
        Assert::isInstanceOf($order, OrderInterface::class);

        // todo check if there's already an affiliate order associated with this order. It's an edge case and maybe not even possible, but we don't want to break the checkout

        $affiliateOrder = $this->affiliateOrderFactory->createWithData(
            $program,
            $order,
            $this->cookieHandler->value($request),
            $request->getClientIp() ?? '0.0.0.0'
        );

        // we don't flush since we are listening to the pre_complete event and when the order completes
        // the entity manager is flushed and this entity is saved along with the order
        $this->getManager($affiliateOrder)->persist($affiliateOrder);
    }
}
