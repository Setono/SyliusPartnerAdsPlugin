<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\UrlProvider;

use Setono\SyliusPartnerAdsPlugin\Exception\MissingVariableInUrlException;

final readonly class NotifyUrlProvider implements NotifyUrlProviderInterface
{
    public function __construct(private string $url)
    {
    }

    public function provide(int $programId, string $orderId, float $value, int $partnerId, string $ip): string
    {
        // Values are URL-encoded so they are safe to interpolate into the query string. This also
        // prevents a value from accidentally introducing another placeholder during replacement.
        $replacements = [
            '{program_id}' => rawurlencode((string) $programId),
            '{partner_id}' => rawurlencode((string) $partnerId),
            '{ip}' => rawurlencode($ip),
            '{order_id}' => rawurlencode($orderId),
            '{value}' => rawurlencode((string) $value),
        ];

        foreach (array_keys($replacements) as $variable) {
            if (!str_contains($this->url, $variable)) {
                throw new MissingVariableInUrlException($this->url, $variable);
            }
        }

        return strtr($this->url, $replacements);
    }
}
