<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Tests\Unit\Calculator;

use PHPUnit\Framework\Attributes\Test;
use PHPUnit\Framework\TestCase;
use Prophecy\PhpUnit\ProphecyTrait;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculator;
use Sylius\Component\Core\Model\OrderInterface;

final class OrderTotalCalculatorTest extends TestCase
{
    use ProphecyTrait;

    #[Test]
    public function it_returns_correct_total(): void
    {
        $calculator = new OrderTotalCalculator();

        $tests = [
            ['total' => 0, 'shipping' => 0, 'expected' => 0.0],
            ['total' => 10, 'shipping' => 9, 'expected' => 0.01],
            ['total' => 123456, 'shipping' => 1245, 'expected' => 1222.11],
        ];

        foreach ($tests as $test) {
            $order = $this->prophesize(OrderInterface::class);
            $order->getTotal()->willReturn($test['total']);
            $order->getShippingTotal()->willReturn($test['shipping']);

            self::assertSame($test['expected'], $calculator->get($order->reveal()));
        }
    }
}
