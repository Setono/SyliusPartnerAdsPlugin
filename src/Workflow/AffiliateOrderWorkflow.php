<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Workflow;

use Setono\SyliusPartnerAdsPlugin\Model\AffiliateOrderInterface;
use Symfony\Component\Workflow\Transition;

final class AffiliateOrderWorkflow
{
    public const NAME = 'setono_sylius_partner_ads_affiliate_order';

    public const TRANSITION_START = 'start';

    public const TRANSITION_PROCESS = 'process';

    public const TRANSITION_SEND = 'send';

    public const TRANSITION_FAIL = 'fail';

    private function __construct()
    {
    }

    /**
     * @return list<string>
     */
    public static function getStates(): array
    {
        return [
            AffiliateOrderInterface::STATE_FAILED,
            AffiliateOrderInterface::STATE_INITIAL,
            AffiliateOrderInterface::STATE_PENDING,
            AffiliateOrderInterface::STATE_PROCESSING,
            AffiliateOrderInterface::STATE_SENT,
        ];
    }

    public static function getConfig(): array
    {
        $transitions = [];
        foreach (self::getTransitions() as $transition) {
            $transitions[$transition->getName()] = [
                'from' => $transition->getFroms(),
                'to' => $transition->getTos(),
            ];
        }

        return [
            self::NAME => [
                'type' => 'state_machine',
                'marking_store' => [
                    'type' => 'method',
                    'property' => 'state',
                ],
                'supports' => AffiliateOrderInterface::class,
                'initial_marking' => AffiliateOrderInterface::STATE_INITIAL,
                'places' => self::getStates(),
                'transitions' => $transitions,
            ],
        ];
    }

    /**
     * @return array<array-key, Transition>
     */
    public static function getTransitions(): array
    {
        return [
            new Transition(self::TRANSITION_START, AffiliateOrderInterface::STATE_INITIAL, AffiliateOrderInterface::STATE_PENDING),
            new Transition(self::TRANSITION_PROCESS, AffiliateOrderInterface::STATE_PENDING, AffiliateOrderInterface::STATE_PROCESSING),
            new Transition(self::TRANSITION_SEND, AffiliateOrderInterface::STATE_PROCESSING, AffiliateOrderInterface::STATE_SENT),
            new Transition(self::TRANSITION_FAIL, self::getStates(), AffiliateOrderInterface::STATE_FAILED),
        ];
    }
}
