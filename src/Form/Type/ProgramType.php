<?php

declare(strict_types=1);

namespace Setono\SyliusPartnerAdsPlugin\Form\Type;

use Sylius\Bundle\ChannelBundle\Form\Type\ChannelChoiceType;
use Sylius\Bundle\ResourceBundle\Form\Type\AbstractResourceType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\FormBuilderInterface;

final class ProgramType extends AbstractResourceType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('programId', IntegerType::class, [
                'label' => 'setono_sylius_partner_ads.ui.program_id',
                'attr' => [
                    'placeholder' => 'setono_sylius_partner_ads.ui.program_id_placeholder',
                    'min' => 1,
                ],
            ])
            ->add('enabled', CheckboxType::class, [
                'required' => false,
                'label' => 'sylius.form.product.enabled',
            ])
            ->add('channel', ChannelChoiceType::class, [
                'label' => 'setono_sylius_partner_ads.ui.channel',
                'attr' => [
                    'placeholder' => 'setono_sylius_partner_ads.ui.select_channel',
                ],
            ])
        ;
    }
}
