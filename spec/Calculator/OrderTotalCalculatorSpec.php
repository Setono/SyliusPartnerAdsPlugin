<?php

declare(strict_types=1);

namespace spec\Setono\SyliusPartnerAdsPlugin\Calculator;

use PhpSpec\ObjectBehavior;
use Setono\SyliusPartnerAdsPlugin\Calculator\OrderTotalCalculator;
use Sylius\Component\Core\Model\OrderInterface;

class OrderTotalCalculatorSpec extends ObjectBehavior
{
    public function it_is_initializable(): void
    {
        $this->shouldHaveType(OrderTotalCalculator::class);
    }

    public function it_returns_correct_total(OrderInterface $order): void
    {
        $tests = [
            [
                'total' => 0,
                'shipping' => 0,
                'expected' => 0.0,
            ],
            [
                'total' => 10,
                'shipping' => 9,
                'expected' => 0.01,
            ],
            [
                'total' => 123456,
                'shipping' => 1245,
                'expected' => 1222.11,
            ],
        ];

        foreach ($tests as $test) {
            $order->getTotal()->willReturn($test['total']);
            $order->getShippingTotal()->willReturn($test['shipping']);

            $this->get($order)->shouldReturn($test['expected']);
        }
    }
}
