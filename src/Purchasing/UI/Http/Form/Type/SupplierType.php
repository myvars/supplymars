<?php

namespace App\Purchasing\UI\Http\Form\Type;

use App\Purchasing\Domain\Model\Supplier\SupplierColourScheme;
use App\Purchasing\UI\Http\Form\Model\SupplierForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<SupplierForm>
 */
final class SupplierType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
            ->add('name', null, [
                'label' => 'Supplier Name',
            ])
            ->add('colourScheme', EnumType::class, [
                'class' => SupplierColourScheme::class,
                'label' => 'Colour Scheme',
                'choice_label' => fn (SupplierColourScheme $scheme): string => $scheme->label(),
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupplierForm::class,
        ]);
    }
}
