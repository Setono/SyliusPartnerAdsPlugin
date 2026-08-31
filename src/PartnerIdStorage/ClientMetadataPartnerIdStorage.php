<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\PartnerIdStorage;

use Setono\Client\Metadata;
use Setono\ClientBundle\Context\ClientContextInterface;
use Setono\SyliusPartnerAdsPlugin\Parser\PartnerIdParser;

/**
 * Stores the partner id in the metadata of the visitor's client (setono/client-bundle). The bundle recognises
 * returning visitors through its own cookie and persists changed metadata itself at the end of the request, so the
 * plugin does not need a cookie of its own.
 */
final readonly class ClientMetadataPartnerIdStorage implements PartnerIdStorageInterface
{
    /**
     * Namespaced on purpose: the client metadata is a single key/value store shared by every bundle in the application
     */
    public const METADATA_KEY = 'setono_sylius_partner_ads.partner_id';

    private const SECONDS_PER_DAY = 86_400;

    /**
     * @param int $attributionWindow the number of days a stored partner id stays valid
     */
    public function __construct(
        private ClientContextInterface $clientContext,
        private int $attributionWindow,
    ) {
    }

    public function store(int $partnerId): void
    {
        $this->getMetadata()->set(self::METADATA_KEY, $partnerId, $this->attributionWindow * self::SECONDS_PER_DAY);
    }

    public function get(): ?int
    {
        $metadata = $this->getMetadata();

        // has() is false for an expired key, which the bundle then prunes and persists on its own
        if (!$metadata->has(self::METADATA_KEY)) {
            return null;
        }

        // The metadata is a mixed store that may have been edited outside this plugin, so the value is validated
        // again. An invalid value is treated as absent but deliberately NOT removed: this method runs inside the
        // customer's checkout request, and a write here would make the bundle flush the metadata during checkout.
        return PartnerIdParser::parse($metadata->get(self::METADATA_KEY));
    }

    private function getMetadata(): Metadata
    {
        return $this->clientContext->getClient()->metadata;
    }
}
