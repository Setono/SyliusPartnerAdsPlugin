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
     * Returns the Partner Ads partner id held by the cookie. Callers must check has() first: this method throws
     * if the cookie is missing or does not hold a valid partner id.
     */
    public function get(Request $request): int;

    /**
     * Returns true if the request has the cookie and it holds a valid (positive integer) partner id.
     * A missing, empty, or tampered cookie is treated as absent.
     */
    public function has(Request $request): bool;
}
