<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Manufacturer;
use App\Entity\PriceModel;
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
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;
use Symfony\Component\Validator\Constraints\Valid;

class ProductType extends AbstractType
{
    public function __construct(
//        private readonly IntegerToPercentageTransformer $transformer,
//        private readonly UrlGeneratorInterface $router,
    )
    {
    }
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Product Name',
                'row_attr' => ['class' => 'sm:col-span-2 mb-4'],
                'priority' => 4,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Category',
//                'attr' => [
//                    'data-controller' => 'dependent-field',
//                    'data-dependent-field-url-value' => $this->router->generate('app_category_subcategories', ['id' => '%id%']),
//                    'data-dependent-field-dependent-value' => 'product_subcategory',
//                    'data-action' => 'dependent-field#updateDependent',
//                ],
                'priority' => 3,
            ])
            ->add('stock', null, [
                'label' => 'Stock Level',
                'priority' => 2,
            ])
            ->add('subcategory', EntityType::class, [
                'class' => Subcategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Subcategory',
                'priority' => 1,
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
            ->add('MfrPartNumber', null, [
                'label' => 'Mfr Part Number',
            ])
            ->add('defaultMarkup', PercentType::class, [
                'scale' => 3,
                'type' => 'integer',
                'label' => 'Product Markup %',
            ])
            ->add('markup', PercentType::class, [
                'scale' => 3,
                'type' => 'integer',
                'label' => 'Markup %',
                'disabled' => true,
            ])
            ->add('priceModel', EntityType::class, [
                'class' => PriceModel::class,
                'choice_label' => 'name',
                'label' => 'Price Model',
                'placeholder' => 'Choose a Price Model',
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
                'disabled' => true,
            ])
            ->add('sellPriceIncVat', MoneyType::class, [
                'currency' => 'GBP',
                'label' => 'Sell Price inc VAT',
                'disabled' => true,
            ])
            ->add('isActive', null, [
                'label' => 'Active',
                'row_attr' => ['class' => 'sm:col-span-2 mb-4'],
            ])
        ;

//        $builder->get('markup')->addModelTransformer($this->transformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
