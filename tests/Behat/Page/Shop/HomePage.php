<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusPartnerAdsPlugin\Behat\Page\Shop;

use Sylius\Behat\Page\Shop\HomePage as BaseHomePage;

class HomePage extends BaseHomePage
{
    public function setPartnerAdsQuery()
    {
        $this->open(['paid' => 'test']);
    }
}
