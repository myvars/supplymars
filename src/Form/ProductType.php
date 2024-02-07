<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Manufacturer;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\User;
use App\Entity\VatRate;
use App\Form\DataTransformer\IntegerToPercentageTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function __construct(private readonly IntegerToPercentageTransformer $transformer)
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Product Name',
                'row_attr' => ['class' => 'sm:col-span-2 mb-4'],
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Category',
            ])
            ->add('stock', null, [
                'label' => 'Stock Level',
            ])
            ->add('subcategory', EntityType::class, [
                'class' => Subcategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Subcategory',
            ])
            ->add('leadTimeDays', null, [
                'label' => 'Lead Time (days)',
            ])
            ->add('manufacturer', EntityType::class, [
                'class' => Manufacturer::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Manufacturer',
            ])
            ->add('weight', null, [
                'label' => 'Weight (grams)',
            ])
            ->add('vatRate', EntityType::class, [
                'class' => VatRate::class,
                'choice_label' => 'name',
                'label' => 'VAT Rate',
                'placeholder' => 'Choose a VAT Rate',
            ])
            ->add('MfrPartNumber', null, [
                'label' => 'Mfr Part Number',
            ])
            ->add('markup', PercentType::class, [
                'scale' => 2,
                'type' => 'integer',
                'label' => 'Markup %',
            ])
            ->add('cost', MoneyType::class, [
                'currency' => 'GBP',
                'label' => 'Cost',
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'label' => 'Product Manager',
                'placeholder' => 'Choose a Product Manager',
            ])
            ->add('sellPrice', MoneyType::class, [
                'currency' => 'GBP',
                'label' => 'Sell Price',
            ])
            ->add('isActive', null, [
                'label' => 'Active',
                'row_attr' => ['class' => 'sm:col-span-2 mb-4'],
            ])
        ;

        $builder->get('markup')->addModelTransformer($this->transformer);
        $builder->get('cost')->addModelTransformer($this->transformer);
        $builder->get('sellPrice')->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
