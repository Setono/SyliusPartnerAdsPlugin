<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Exception;

final class NoDefinedTransportsException extends \InvalidArgumentException
{
    public function __construct(string $message = 'No defined transports. Define one in the symfony.messenger.transports.* configuration')
    {
        parent::__construct($message);
    }
}
