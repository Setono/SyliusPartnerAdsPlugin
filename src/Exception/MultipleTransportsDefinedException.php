<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Exception;

final class MultipleTransportsDefinedException extends \InvalidArgumentException
{
    public function __construct(string $message = 'Multiple transports defined in symfony.messenger.transports.* configuration. Define which one you want to use for this plugin.')
    {
        parent::__construct($message);
    }
}
