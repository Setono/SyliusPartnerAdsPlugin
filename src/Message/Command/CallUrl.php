<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Message\Command;

final class CallUrl
{
    /**
     * @var string
     */
    private $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    /**
     * @return string
     */
    public function getUrl(): string
    {
        return $this->url;
    }
}
