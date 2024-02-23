<?php

namespace App\Form;

use App\Entity\PriceModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PriceModelType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Price Model Name',
            ])
            ->add('description', null, [
                'label' => 'Description',
            ])
            ->add('modelTag', null, [
                'label' => 'Model Tag',
            ])
            ->add('isActive', null, [
                'label' => 'Active',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PriceModel::class,
        ]);
    }
}
