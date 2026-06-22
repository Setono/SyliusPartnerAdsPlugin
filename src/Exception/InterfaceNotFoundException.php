<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Exception;

use RuntimeException;

final class InterfaceNotFoundException extends RuntimeException
{
    public function __construct(private readonly string $interface)
    {
        parent::__construct(sprintf('The interface "%s" was not found', $this->interface));
    }

    public function getInterface(): string
    {
        return $this->interface;
    }
}
