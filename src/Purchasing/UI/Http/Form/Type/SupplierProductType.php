<?php

namespace App\Purchasing\UI\Http\Form\Type;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Repository\SupplierCategoryRepository;
use App\Purchasing\UI\Http\Form\Model\SupplierProductForm;
use App\Purchasing\UI\Http\Form\Type\Field\SupplierSubcategoryIdType;
use App\Shared\UI\Form\DataTransformer\IdToEntityTransformerFactory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

final class SupplierProductType extends AbstractType
{
    public function __construct(
        private readonly SupplierCategoryRepository $categories,
        private readonly IdToEntityTransformerFactory $transformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
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
                'property_path' => 'supplierId',
            ])
            ->add('category', EntityType::class, [
                'class' => SupplierCategory::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Category',
                'attr' => ['data-action' => 'change->submit-form#submitForm'],
                'priority' => 3,
                'property_path' => 'supplierCategoryId',
            ])
            ->add('manufacturer', EntityType::class, [
                'class' => SupplierManufacturer::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Manufacturer',
                'priority' => 1,
                'property_path' => 'supplierManufacturerId',
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
            ->add('product', IntegerType::class, [
                'label' => 'Mapped Product Id',
                'invalid_message' => 'Product not found',
                'property_path' => 'productId',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
            ])
        ;

        $builder->add('auto-update', SubmitType::class, [
            'attr' => ['class' => 'hidden-submit-button', 'data-submit-form-target' => 'submit'],
        ]);

        $builder->addDependent(
            'subcategory',
            'category',
            function (DependentField $field, ?int $supplierCategoryId): void {
                $supplierCategory = $supplierCategoryId !== null
                    ? $this->categories->get(SupplierCategoryId::fromInt($supplierCategoryId))
                    : null;
                $field->add(SupplierSubcategoryIdType::class, [
                    'choices' => $supplierCategory?->getSupplierSubcategories() ?? [],
                    'priority' => 2,
                ]);
            });

        $builder->get('supplier')
            ->addModelTransformer($this->transformer->for(Supplier::class));
        $builder->get('category')
            ->addModelTransformer($this->transformer->for(SupplierCategory::class));
        $builder->get('manufacturer')
            ->addModelTransformer($this->transformer->for(SupplierManufacturer::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupplierProductForm::class,
        ]);
    }
}
