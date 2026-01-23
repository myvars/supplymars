<?php

namespace App\Pricing\UI\Http\Form\Type;

use App\Pricing\UI\Http\Form\Model\VatRateForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<VatRateForm>
 */
final class VatRateType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
            ->add('name', null, [
                'label' => 'VAT Rate Name',
            ])
            ->add('rate', PercentType::class, [
                'scale' => 2,
                'type' => 'integer',
                'label' => 'VAT Rate %',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => VatRateForm::class,
        ]);
    }
}
