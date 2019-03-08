<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Exception;

final class NotExactlyOneBusDefinedException extends \InvalidArgumentException
{
    public function __construct(string $message = 'There is either 0 or more than one bus defined. You need to explicitly define the command bus to use in the configuration of this plugin.')
    {
        parent::__construct($message);
    }
}
