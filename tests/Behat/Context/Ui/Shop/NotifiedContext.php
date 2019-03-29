<?php

declare(strict_types=1);

namespace Tests\Setono\SyliusPartnerAdsPlugin\Behat\Context\Ui\Shop;

use Behat\Behat\Context\Context;
use Setono\SyliusPartnerAdsPlugin\Model\Program;
use Setono\SyliusPartnerAdsPlugin\Notifier\NotifierInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\ProgramRepositoryInterface;
use Sylius\Component\Channel\Context\CachedPerRequestChannelContext;
use Tests\Setono\SyliusPartnerAdsPlugin\Behat\Page\Shop\HomePage;
use Webmozart\Assert\Assert;

final class NotifiedContext implements Context
{
    /**
     * @var HomePage
     */
    private $homePage;

    /**
     * @var NotifierInterface
     */
    private $notifer;

    /**
     * @var ProgramRepositoryInterface
     */
    private $programRepository;

    /**
     * @var CachedPerRequestChannelContext
     */
    private $channelContext;

    public function __construct(HomePage $homePage, NotifierInterface $notifier, ProgramRepositoryInterface $programRepository, CachedPerRequestChannelContext $channelContext)
    {
        $this->homePage = $homePage;
        $this->notifer = $notifier;
        $this->programRepository = $programRepository;
        $this->channelContext = $channelContext;
    }

    /**
     * @Given a partner ads program with program id :programId is made for current channel
     */
    public function aPartnerAdsProgramWithProgramIdIsMadeForCurrentChannel(int $programId)
    {
        $program = new Program();
        $program->setChannel($this->channelContext->getChannel());
        $program->setProgramId($programId);
        $program->enable();
        $this->programRepository->add($program);
    }

    /**
     * @Then partner ads should have been notified
     */
    public function partnerAdsShouldHaveBeenNotified()
    {
        Assert::true($this->notifer->hasBeenNotified());
    }

    /**
     * @Given a partner ads cookie has been set
     */
    public function aPartnerAdsCookieHasBeenSet()
    {
        $this->homePage->setPartnerAdsQuery();
    }

    /**
     * @Given I enter the shop from partner ads link
     */
    public function iEnterTheShopFromPartnerAdsLink()
    {
        $this->homePage->setPartnerAdsQuery();
    }
}
