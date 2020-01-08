<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Exception;

use InvalidArgumentException;
use function Safe\sprintf;

final class MissingVariableInUrlException extends InvalidArgumentException
{
    /** @var string */
    private $url;

    /** @var string */
    private $missingVariable;

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
