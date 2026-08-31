<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\EventListener;

use Setono\SyliusPartnerAdsPlugin\Model\ConversionInterface;
use Setono\SyliusPartnerAdsPlugin\PartnerIdStorage\PartnerIdStorageInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ConversionRepositoryInterface;
use Sylius\Component\Core\Model\OrderInterface;
use Sylius\Component\Resource\Factory\FactoryInterface;
use Symfony\Component\EventDispatcher\EventSubscriberInterface;
use Symfony\Component\EventDispatcher\GenericEvent;
use Webmozart\Assert\Assert;

/**
 * Creates a pending conversion when an order is completed by a customer that was referred by a Partner Ads partner.
 * The conversion is sent to Partner Ads later by the ProcessConversionsCommand.
 *
 * Design notes - this listener runs inside the customer's checkout request, so it must never be able to fail:
 *
 * - The only work done here is one read of the visitor's partner id and a single insert. With the default storage
 *   the read is one lazy SELECT on the client metadata by setono/client-bundle; the storage never writes here. (If
 *   the stored partner id has expired, the bundle prunes it and persists that itself at the end of the request,
 *   after the order and the conversion have been committed - its own code path, accepted rather than worked around.)
 *
 * - There is deliberately NO unique constraint on the conversion's order (the association is many-to-one).
 *   Two concurrent checkout-complete requests for the same cart (a double click on "place order", a browser
 *   retry, a payment return racing the customer's return) both dispatch sylius.order.post_complete before
 *   either has committed. The existence check below cannot see the other request's uncommitted insert, so a
 *   unique constraint would be the only thing stopping the duplicate - and it would stop it by throwing inside
 *   the checkout, failing the order for the customer. Instead we accept the (rare) duplicate row and guarantee
 *   at most one notification per order in ProcessConversionsCommand, where a duplicate is harmless.
 *
 * - The insert is not wrapped in a try/catch either: a failed flush closes Doctrine's entity manager for the
 *   rest of the request, so catching would only hide the error while every later listener and flush breaks.
 *   Removing the constraint removes the only realistic way for this flush to fail.
 */
final readonly class CreateConversionSubscriber implements EventSubscriberInterface
{
    public function __construct(
        private PartnerIdStorageInterface $partnerIdStorage,
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

        // null when the visitor was not referred, the attribution window has passed, or there is no request at all
        $partnerId = $this->partnerIdStorage->get();
        if (null === $partnerId) {
            return;
        }

        // best effort only: this prevents duplicates when the event is dispatched more than once sequentially,
        // but cannot see a concurrent request's uncommitted insert (see the class docblock)
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
