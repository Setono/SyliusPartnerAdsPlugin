<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\EventListener;

use Setono\SyliusPartnerAdsPlugin\CookieHandler\CookieHandlerInterface;
use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Symfony\Component\HttpFoundation\RequestStack;
use Webmozart\Assert\Assert;

/**
 * Creates a pending conversion when an order is completed by a customer that was referred by a Partner Ads partner.
 * The conversion is sent to Partner Ads later - when the order has been paid - by the ProcessConversionsCommand.
 */
final readonly class CreateConversionSubscriber implements EventSubscriberInterface
{
    /**
     * @param FactoryInterface<ConversionInterface> $conversionFactory
     */
    public function __construct(
        private RequestStack $requestStack,
        private CookieHandlerInterface $cookieHandler,
        private FactoryInterface $conversionFactory,
        private ConversionRepositoryInterface $conversionRepository,
    ) {
    }

    public static function getSubscribedEvents(): array
    {
        return [
            'sylius.order.post_complete' => 'createConversion',
        ];
    }

    public function createConversion(GenericEvent $event): void
    {
        $order = $event->getSubject();
        if (!$order instanceof OrderInterface) {
            return;
        }

        $request = $this->requestStack->getMainRequest();
        if (null === $request) {
            return;
        }

        if (!$this->cookieHandler->has($request)) {
            return;
        }

        // a tampered or mangled cookie casts to a non-positive integer - do not attribute the order in that case
        $partnerId = $this->cookieHandler->get($request);
        if ($partnerId <= 0) {
            return;
        }

        // the database has a unique constraint on the order, but the post_complete event could be
        // dispatched from custom code, so do not fail if the order already has a conversion
        if (null !== $this->conversionRepository->findOneByOrder($order)) {
            return;
        }

        $conversion = $this->conversionFactory->createNew();
        Assert::isInstanceOf($conversion, ConversionInterface::class);

        $conversion->setOrder($order);
        $conversion->setPartnerId($partnerId);

        $this->conversionRepository->add($conversion);
    }
}
