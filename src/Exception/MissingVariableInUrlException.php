<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Exception;

use InvalidArgumentException;

final class MissingVariableInUrlException extends InvalidArgumentException
{
    public function __construct(private readonly string $url, private readonly string $missingVariable)
    {
        parent::__construct(sprintf('The URL %s is missing variable %s', $this->url, $this->missingVariable));
    }

    public function getUrl(): string
    {
        return $this->url;
    }

    public function getMissingVariable(): string
    {
        return $this->missingVariable;
    }
}
