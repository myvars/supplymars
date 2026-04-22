<?php

declare(strict_types=1);

namespace App\Catalog\UI\Http\Form\Type;

use App\Catalog\UI\Http\Form\Model\ManufacturerForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ManufacturerForm>
 */
final class ManufacturerType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
            ->add('name', null, [
                'label' => 'Manufacturer Name',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ManufacturerForm::class,
        ]);
    }
}
