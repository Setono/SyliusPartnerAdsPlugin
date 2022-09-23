<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Message\Command;

use Setono\SyliusPartnerAdsPlugin\Model\AffiliateOrderInterface;
use Webmozart\Assert\Assert;

final class ProcessAffiliateOrder implements CommandInterface
{
    /** @readonly */
    public int $invitation;

    /**
     * @param int|AffiliateOrderInterface $invitation
     */
    public function __construct($invitation)
    {
        if ($invitation instanceof AffiliateOrderInterface) {
            $invitation = $invitation->getId();
        }

        Assert::integer($invitation);

        $this->invitation = $invitation;
    }
}
