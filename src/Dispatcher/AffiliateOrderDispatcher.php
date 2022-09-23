<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Dispatcher;

use Doctrine\Persistence\ManagerRegistry;
use Setono\DoctrineObjectManagerTrait\ORM\ORMManagerTrait;
use Setono\SyliusPartnerAdsPlugin\Message\Command\ProcessAffiliateOrder;
use Setono\SyliusPartnerAdsPlugin\Model\AffiliateOrderInterface;
use Setono\SyliusPartnerAdsPlugin\Repository\AffiliateOrderRepositoryInterface;
use Setono\SyliusPartnerAdsPlugin\Workflow\AffiliateOrderWorkflow;
use Symfony\Component\Messenger\MessageBusInterface;
use Symfony\Component\Workflow\Registry;
use Symfony\Component\Workflow\WorkflowInterface;

final class AffiliateOrderDispatcher implements AffiliateOrderDispatcherInterface
{
    use ORMManagerTrait;

    private ?WorkflowInterface $workflow = null;

    private MessageBusInterface $commandBus;

    private AffiliateOrderRepositoryInterface $affiliateOrderRepository;

    private Registry $workflowRegistry;

    public function __construct(
        ManagerRegistry $managerRegistry,
        MessageBusInterface $commandBus,
        AffiliateOrderRepositoryInterface $invitationRepository,
        Registry $workflowRegistry
    ) {
        $this->managerRegistry = $managerRegistry;
        $this->commandBus = $commandBus;
        $this->affiliateOrderRepository = $invitationRepository;
        $this->workflowRegistry = $workflowRegistry;
    }

    public function dispatch(): void
    {
        $affiliateOrders = $this->affiliateOrderRepository->findNew();

        foreach ($affiliateOrders as $affiliateOrder) {
            $workflow = $this->getWorkflow($affiliateOrder);
            if (!$workflow->can($affiliateOrder, AffiliateOrderWorkflow::TRANSITION_START)) {
                continue;
            }

            $workflow->apply($affiliateOrder, AffiliateOrderWorkflow::TRANSITION_START);

            $this->getManager($affiliateOrder)->flush();

            $this->commandBus->dispatch(new ProcessAffiliateOrder($affiliateOrder));
        }
    }

    private function getWorkflow(AffiliateOrderInterface $affiliateOrder): WorkflowInterface
    {
        if (null === $this->workflow) {
            $this->workflow = $this->workflowRegistry->get($affiliateOrder, AffiliateOrderWorkflow::NAME);
        }

        return $this->workflow;
    }
}
