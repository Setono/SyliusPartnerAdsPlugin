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
     * Returns the cookie value which is a Partner Ads partner id.
     * If the request is null, it will use the main request from the request stack
     *
     * todo what if the cookie isn't set on the request?
     */
    public function value(Request $request = null): int;

    /**
     * Returns true if the request has the cookie set.
     * If the request is null, it will use the main request from the request stack
     */
    public function isset(Request $request = null): bool;
}
