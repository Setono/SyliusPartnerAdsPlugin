<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Menu;

use Knp\Menu\Integration\Symfony\RoutingExtension;
use Knp\Menu\MenuFactory;
use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\Argument;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusPartnerAdsPlugin\Menu\AdminMenuListener;
use Sylius\Bundle\UiBundle\Menu\Event\MenuBuilderEvent;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

final class AdminMenuListenerTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_adds_the_partner_ads_item_to_the_marketing_menu(): void
    {
        $factory = $this->createFactory();
        $menu = $factory->createItem('root');
        // The first child is intentionally not the marketing menu so we can prove the item is
        // added to the marketing menu specifically, not just to the first child.
        $menu->addChild('catalog');
        $menu->addChild('marketing');

        (new AdminMenuListener())->addAdminMenuItems(new MenuBuilderEvent($factory, $menu));

        $marketing = $menu->getChild('marketing');
        self::assertNotNull($marketing);

        $catalog = $menu->getChild('catalog');
        self::assertNotNull($catalog);

        $item = $marketing->getChild('partner_ads');
        self::assertNotNull($item);
        self::assertNull($catalog->getChild('partner_ads'));
        self::assertSame('setono_sylius_partner_ads.ui.partner_ads', $item->getLabel());
        self::assertSame('tabler:heart-handshake', $item->getLabelAttribute('icon'));
        self::assertSame('/admin/partner-ads', $item->getUri());
    }

    #[Test]
    public function it_falls_back_to_the_first_child_when_there_is_no_marketing_menu(): void
    {
        $factory = $this->createFactory();
        $menu = $factory->createItem('root');
        $menu->addChild('catalog');

        (new AdminMenuListener())->addAdminMenuItems(new MenuBuilderEvent($factory, $menu));

        $catalog = $menu->getChild('catalog');
        self::assertNotNull($catalog);

        $item = $catalog->getChild('partner_ads');
        self::assertNotNull($item);
        self::assertSame('tabler:heart-handshake', $item->getLabelAttribute('icon'));
        self::assertSame('/admin/partner-ads', $item->getUri());
    }

    private function createFactory(): MenuFactory
    {
        $urlGenerator = $this->prophesize(UrlGeneratorInterface::class);
        $urlGenerator->generate('setono_sylius_partner_ads_admin_program_index', Argument::cetera())
            ->willReturn('/admin/partner-ads');

        $factory = new MenuFactory();
        $factory->addExtension(new RoutingExtension($urlGenerator->reveal()));

        return $factory;
    }
}
