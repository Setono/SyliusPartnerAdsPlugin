<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\UrlProvider;

use Setono\SyliusPartnerAdsPlugin\Exception\MissingVariableInUrlException;

final class NotifyUrlProvider implements NotifyUrlProviderInterface
{
    private string $url;

    public function __construct(string $url)
    {
        $this->url = $url;
    }

    public function provide(int $programId, string $orderId, float $value, int $partnerId, string $ip): string
    {
        $variables = ['{program_id}', '{partner_id}', '{ip}', '{order_id}', '{value}'];

        foreach ($variables as $variable) {
            if (mb_strpos($this->url, $variable) === false) {
                throw new MissingVariableInUrlException($this->url, $variable);
            }
        }

        return str_replace(
            ['{program_id}', '{partner_id}', '{ip}', '{order_id}', '{value}'],
            [$programId, $partnerId, $ip, $orderId, $value],
            $this->url,
        );
    }
}
