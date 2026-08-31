<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\PartnerIdStorage;

interface PartnerIdStorageInterface
{
    /**
     * Stores the partner id for the current visitor, replacing any previously stored partner id (last click wins)
     * and restarting the attribution window
     */
    public function store(int $partnerId): void;

    /**
     * Returns the partner id stored for the current visitor, or null if nothing is stored, the attribution window
     * has passed, or the stored value is not a valid partner id (a positive integer)
     */
    public function get(): ?int;
}
