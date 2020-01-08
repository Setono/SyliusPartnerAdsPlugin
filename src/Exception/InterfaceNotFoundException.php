<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Exception;

use RuntimeException;
use function Safe\sprintf;

final class InterfaceNotFoundException extends RuntimeException
{
    /** @var string */
    private $interface;

    public function __construct(string $interface)
    {
        $this->interface = $interface;

        parent::__construct(sprintf('The interface "%s" was not found', $this->interface));
    }

    public function getInterface(): string
    {
        return $this->interface;
    }
}
