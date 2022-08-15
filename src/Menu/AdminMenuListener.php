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

        $configuration = $menu->getChild('marketing');

        if (null !== $configuration) {
            $this->addChild($configuration);
        } else {
            $this->addChild($menu->getFirstChild());
        }
    }

    private function addChild(ItemInterface $item): void
    {
        $item
            ->addChild('partner_ads', [
                'route' => 'setono_sylius_partner_ads_admin_program_index',
            ])
            ->setLabel('setono_sylius_partner_ads.ui.partner_ads')
            ->setLabelAttribute('icon', 'handshake outline')
        ;
    }
}
