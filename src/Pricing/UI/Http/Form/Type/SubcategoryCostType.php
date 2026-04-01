<?php

namespace App\Pricing\UI\Http\Form\Type;

use App\Pricing\UI\Http\Form\Model\SubcategoryCostForm;
use App\Shared\Domain\ValueObject\PriceModel;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<SubcategoryCostForm>
 */
final class SubcategoryCostType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('defaultMarkup', PercentType::class, [
                'scale' => 3,
                'type' => 'integer',
                'label' => 'Subcategory Markup %',
                'help' => 'Overrides the category markup when set.',
            ])
            ->add('priceModel', EnumType::class, [
                'class' => PriceModel::class,
                'choice_label' => fn (PriceModel $priceModel): string => $priceModel->getName(),
                'label' => 'Price Model',
                'placeholder' => 'Choose a Price Model',
                'help' => 'Controls how sell prices are rounded. Use None to inherit from the category.',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubcategoryCostForm::class,
        ]);
    }
}
