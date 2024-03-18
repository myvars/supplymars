<?php

namespace App\Form;

use App\Entity\VatRate;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VatRateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'VAT Rate Name',
            ])
            ->add('rate', PercentType::class, [
                'scale' => 2,
                'type' => 'integer',
                'label' => 'Rate %',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VatRate::class,
        ]);
    }
}
