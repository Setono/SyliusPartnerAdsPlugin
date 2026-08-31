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
 * The conversion is sent to Partner Ads later by the ProcessConversionsCommand.
 *
 * Design notes - this listener runs inside the customer's checkout request, so it must never be able to fail:
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
