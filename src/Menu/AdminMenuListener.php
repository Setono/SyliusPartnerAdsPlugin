<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Menu;

use Knp\Menu\ItemInterface;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;

final class AdminMenuListener
{
    public function addAdminMenuItems(MenuBuilderEvent $event): void
    {
        $menu = $event->getMenu();

        $marketingMenu = $menu->getChild('marketing');

        if (null !== $marketingMenu) {
            $this->addChildren($marketingMenu);
        } else {
            $this->addChildren($menu->getFirstChild());
        }
    }

    private function addChildren(ItemInterface $item): void
    {
        $item
            ->addChild('partner_ads', [
                'route' => 'setono_sylius_partner_ads_admin_program_index',
            ])
            ->setLabel('setono_sylius_partner_ads.ui.partner_ads')
            ->setLabelAttribute('icon', 'tabler:heart-handshake')
        ;

        $item
            ->addChild('partner_ads_conversions', [
                'route' => 'setono_sylius_partner_ads_admin_conversion_index',
            ])
            ->setLabel('setono_sylius_partner_ads.ui.conversions')
            ->setLabelAttribute('icon', 'tabler:coins')
        ;
    }
}
