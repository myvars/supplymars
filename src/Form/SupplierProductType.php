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
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class SupplierProductType extends AbstractType
{
    public function __construct(private readonly ProductToIdTransformer $productToIdTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('name', null, [
                'label' => 'Product Name',
                'priority' => 6,
            ])
            ->add('productCode', null, [
                'label' => 'Product Code',
                'priority' => 5,
            ])
            ->add('supplier', EntityType::class, [
                'class' => Supplier::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Supplier',
                'priority' => 4,
            ])
            ->add('supplierCategory', EntityType::class, [
                'class' => SupplierCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Category',
                'attr' => ['data-action' => 'change->submit-form#submitForm'],
                'priority' => 3,
            ])
            ->add('supplierManufacturer', EntityType::class, [
                'class' => SupplierManufacturer::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Manufacturer',
                'priority' => 1,
            ])
            ->add('mfrPartNumber', null, [
                'label' => 'Manufacturer Part Number',
            ])
            ->add('cost', MoneyType::class, [
                'currency' => 'GBP',
                'label' => 'Cost',
            ])
            ->add('stock', null, [
                'label' => 'Stock Level',
            ])
            ->add('leadTimeDays', null, [
                'label' => 'Lead Time (days)',
            ])
            ->add('weight', null, [
                'label' => 'Weight (grams)',
            ])
            ->add('product', TextType::class, [
                'label' => 'Mapped Product Id',
                'invalid_message' => 'Product not found',
            ])
            ->add('isActive', null, [
                'label' => 'Active',
            ])
        ;

        $builder->add('auto-update', SubmitType::class, [
            'attr' => ['class' => 'hidden-submit-button', 'data-submit-form-target' => 'submit']
        ]);

        $builder->addDependent('supplierSubcategory', 'supplierCategory', function(DependentField $field, ?SupplierCategory $supplierCategory): void {
            $field
                ->add(EntityType::class, [
                    'class' => SupplierSubcategory::class,
                    'choices' => $supplierCategory instanceof SupplierCategory ? $supplierCategory->getSupplierSubcategories() : [],
                    'choice_label' => 'name',
                    'placeholder' => 'Choose a Subcategory',
                    'priority' => 2,
                ]);
        });

        $builder->get('product')->addModelTransformer($this->productToIdTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupplierProduct::class,
        ]);
    }
}
