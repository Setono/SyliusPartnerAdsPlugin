<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\CookieHandler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CookieHandlerInterface
{
    /**
     * Sets the cookie on the given response with the value being $partnerId
     */
    public function set(Response $response, int $partnerId): void;

    /**
     * Removes the cookie on the given response
     */
    public function remove(Response $response): void;

    /**
     * Returns the cookie value which is a Partner Ads partner id
     */
    public function get(Request $request): int;

    /**
     * Returns true if the request has the cookie set
     */
    public function has(Request $request): bool;
}
