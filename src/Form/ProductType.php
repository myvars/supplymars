<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Manufacturer;
use App\Entity\PriceModel;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\User;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ProductType extends AbstractType
{
    public function __construct(
        //        private readonly IntegerToPercentageTransformer $transformer,
        //        private readonly UrlGeneratorInterface $router,
    ) {
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
//                'attr' => [
//                    'data-controller' => 'dependent-field',
//                    'data-dependent-field-url-value' => $this->router->generate('app_category_subcategories', ['id' => '%id%']),
//                    'data-dependent-field-dependent-value' => 'product_subcategory',
//                    'data-action' => 'dependent-field#updateDependent',
//                ],
            ])
            ->add('leadTimeDays', null, [
                'label' => 'Lead Time (days)',
            ])
            ->add('subcategory', EntityType::class, [
                'class' => Subcategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Subcategory',
            ])
            ->add('weight', null, [
                'label' => 'Weight (grams)',
            ])
            ->add('manufacturer', EntityType::class, [
                'class' => Manufacturer::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Manufacturer',
            ])
            ->add('priceModel', EnumType::class, [
                'class' => PriceModel::class,
                'choice_label' => fn (PriceModel $priceModel) => $priceModel->getName(),
                'label' => 'Price Model',
                'placeholder' => 'Choose a Price Model',
            ])
            ->add('mfrPartNumber', null, [
                'label' => 'Manufacturer Part Number',
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
            ->add('defaultMarkup', PercentType::class, [
                'scale' => 3,
                'type' => 'integer',
                'label' => 'Product Markup %',
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
