<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Exception;

use InvalidArgumentException;

final class MissingVariableInUrlException extends InvalidArgumentException
{
    private string $url;

    private string $missingVariable;

    public function __construct(string $url, string $missingVariable)
    {
        $this->url = $url;
        $this->missingVariable = $missingVariable;

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
