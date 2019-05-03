<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\CookieHandler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CookieHandlerInterface
{
    /**
     * Sets the cookie on the given response with the value being $partnerId
     *
     * @param Response $response
     * @param int $partnerId
     */
    public function set(Response $response, int $partnerId): void;

    /**
     * Removes the cookie on the given response
     *
     * @param Response $response
     */
    public function remove(Response $response): void;

    /**
     * Returns the cookie value which is a Partner Ads partner id
     *
     * @param Request $request
     *
     * @return int
     */
    public function get(Request $request): int;

    /**
     * Returns true if the request has the cookie set
     *
     * @param Request $request
     *
     * @return bool
     */
    public function has(Request $request): bool;
}
