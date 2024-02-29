<?php

namespace App\Form;

use App\Entity\Supplier;
use App\Entity\SupplierCategory;
use App\Entity\SupplierManufacturer;
use App\Entity\SupplierProduct;
use App\Entity\SupplierSubcategory;
use App\Form\DataTransformer\ProductToIdTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SupplierProductType extends AbstractType
{


    public function __construct(private readonly ProductToIdTransformer $productToIdTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Product Name',
                'row_attr' => ['class' => 'sm:col-span-2 mb-4'],
            ])
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Supplier',
            ])
            ->add('productCode', null, [
                'label' => 'Product Code',
            ])
            ->add('supplierCategory', EntityType::class, [
                'class' => SupplierCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Category',
            ])
            ->add('mfrPartNumber', null, [
                'label' => 'Manufacturer Part Number',
            ])
            ->add('supplierSubcategory', EntityType::class, [
                'class' => SupplierSubcategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Subcategory',
            ])
            ->add('leadTimeDays', null, [
                'label' => 'Lead Time (days)',
            ])
            ->add('supplierManufacturer', EntityType::class, [
                'class' => SupplierManufacturer::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Manufacturer',
            ])
            ->add('stock', null, [
                'label' => 'Stock Level',
            ])
            ->add('weight', null, [
                'label' => 'Weight (grams)',
            ])
            ->add('cost', MoneyType::class, [
                'currency' => 'GBP',
                'label' => 'Cost',
            ])
            ->add('product', TextType::class, [
                'label' => 'Mapped Product Id',
                'invalid_message' => 'Product not found',
            ])
            ->add('isActive', null, [
                'label' => 'Active',
                'row_attr' => ['class' => 'sm:col-span-2 mb-4'],
            ])
        ;

        $builder->get('product')->addModelTransformer($this->productToIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupplierProduct::class,
        ]);
    }
}
