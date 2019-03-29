<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Model;

use Sylius\Component\Channel\Model\ChannelInterface;
use Sylius\Component\Resource\Model\ToggleableTrait;

class Program implements ProgramInterface
{
    use ToggleableTrait;

    /**
     * @var int
     */
    protected $id;

    /**
     * @var int
     */
    protected $programId;

    /**
     * @var null|ChannelInterface
     */
    protected $channel;

    /**
     * {@inheritdoc}
     */
    public function getId(): ?int
    {
        return $this->id;
    }

    /**
     * {@inheritdoc}
     */
    public function getProgramId(): ?int
    {
        return $this->programId;
    }

    /**
     * {@inheritdoc}
     */
    public function setProgramId(int $programId): void
    {
        $this->programId = $programId;
    }

    /**
     * {@inheritdoc}
     */
    public function getChannel(): ?ChannelInterface
    {
        return $this->channel;
    }

    /**
     * {@inheritdoc}
     */
    public function setChannel(?ChannelInterface $channel): void
    {
        $this->channel = $channel;
    }
}
