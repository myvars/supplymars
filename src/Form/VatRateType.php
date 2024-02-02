<?php

namespace App\Form;

use App\Entity\VatRate;
use App\Form\DataTransformer\IntegerToPercentageTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\CallbackTransformer;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class VatRateType extends AbstractType
{
    public function __construct(private readonly IntegerToPercentageTransformer $transformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name')
            ->add('rate', PercentType::class, [
                'scale' => 2,
                'type' => 'integer',
                'label' => 'Rate %',
            ])
        ;

        $builder->get('rate')->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VatRate::class,
        ]);
    }
}
