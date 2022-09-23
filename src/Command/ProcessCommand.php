<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Command;

use Psr\Log\LoggerAwareInterface;
use Setono\SyliusPartnerAdsPlugin\Dispatcher\AffiliateOrderDispatcherInterface;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Logger\ConsoleLogger;
use Symfony\Component\Console\Output\OutputInterface;

final class ProcessCommand extends Command
{
    protected static $defaultName = 'setono:sylius-partner-ads:process';

    protected static $defaultDescription = 'Process your affiliate orders';

    private AffiliateOrderDispatcherInterface $affiliateOrderDispatcher;

    public function __construct(AffiliateOrderDispatcherInterface $affiliateOrderDispatcher)
    {
        parent::__construct();

        $this->affiliateOrderDispatcher = $affiliateOrderDispatcher;
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        if ($this->affiliateOrderDispatcher instanceof LoggerAwareInterface) {
            $this->affiliateOrderDispatcher->setLogger(new ConsoleLogger($output));
        }

        $this->affiliateOrderDispatcher->dispatch();

        return 0;
    }
}
