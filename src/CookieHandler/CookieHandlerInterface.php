<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\CookieHandler;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

interface CookieHandlerInterface
{
    /**
     * Sets the cookie on the given response with the given value
     *
     * @param Response $response
     * @param string $partnerId
     */
    public function set(Response $response, string $partnerId): void;

    /**
     * Removes the cookie on the given response
     *
     * @param Response $response
     */
    public function remove(Response $response): void;

    /**
     * Returns the cookie value
     *
     * @param Request $request
     * @return string
     */
    public function get(Request $request): string;

    /**
     * Returns true if the request has the cookie set
     *
     * @param Request $request
     * @return bool
     */
    public function has(Request $request): bool;
}
