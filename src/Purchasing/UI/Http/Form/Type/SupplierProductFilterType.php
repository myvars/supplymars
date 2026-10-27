<?php

declare(strict_types=1);

namespace App\Purchasing\UI\Http\Form\Type;

use App\Purchasing\Application\Search\SupplierProductSearchCriteria;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\Supplier\SupplierId;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategoryId;
use App\Purchasing\Domain\Repository\SupplierCategoryRepository;
use App\Purchasing\Domain\Repository\SupplierManufacturerRepository;
use App\Purchasing\Domain\Repository\SupplierRepository;
use App\Purchasing\UI\Http\Form\Type\Field\SupplierCategoryIdType;
use App\Purchasing\UI\Http\Form\Type\Field\SupplierManufacturerIdType;
use App\Purchasing\UI\Http\Form\Type\Field\SupplierSubcategoryIdType;
use App\Shared\UI\Http\Form\DataTransformer\IdToEntityTransformerFactory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * @extends AbstractType<SupplierProductSearchCriteria>
 */
final class SupplierProductFilterType extends AbstractType
{
    public function __construct(
        private readonly SupplierRepository $suppliers,
        private readonly SupplierCategoryRepository $supplierCategories,
        private readonly SupplierManufacturerRepository $supplierManufacturers,
        private readonly IdToEntityTransformerFactory $transformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('supplier', EntityType::class, [
                'label' => 'Supplier',
                'class' => Supplier::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Supplier',
                'attr' => ['data-action' => 'change->submit-form#submitForm'],
                'priority' => 5,
                'property_path' => 'supplierId',
            ])
            ->add('productCode', null, [
                'label' => 'Product Code',
            ])
            ->add('inStock', ChoiceType::class, [
                'label' => 'Stock Level',
                'choices' => [
                    'Any' => null,
                    'In Stock' => true,
                    'Out of Stock' => false,
                ],
            ])
            ->add('isActive', ChoiceType::class, [
                'label' => 'Status',
                'choices' => [
                    'Any' => null,
                    'Active' => true,
                    'Inactive' => false,
                ],
            ])
            ->add('query', HiddenType::class)
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->add('auto-update', SubmitType::class, [
            'attr' => ['class' => 'hidden-submit-button', 'data-submit-form-target' => 'submit'],
        ]);

        $builder->addDependent(
            'supplierCategory',
            'supplier',
            function (
                DependentField $field,
                ?int $supplierId,
            ): void {
                $supplier = $supplierId !== null
                    ? $this->suppliers->get(SupplierId::fromInt($supplierId))
                    : null;
                $field->add(SupplierCategoryIdType::class, [
                    'choices' => $supplier?->getSupplierCategories() ?? [],
                    'placeholder' => 'Any Category',
                    'attr' => ['data-action' => 'change->submit-form#submitForm'],
                    'priority' => 4,
                ]);
            });

        $builder->addDependent(
            'supplierSubcategory',
            'supplierCategory',
            function (
                DependentField $field,
                ?int $supplierCategoryId,
            ): void {
                $supplierCategory = $supplierCategoryId !== null
                    ? $this->supplierCategories->get(SupplierCategoryId::fromInt($supplierCategoryId))
                    : null;
                $field->add(SupplierSubcategoryIdType::class, [
                    'choices' => $supplierCategory?->getSupplierSubcategories() ?? [],
                    'placeholder' => 'Any Subcategory',
                    'attr' => ['data-action' => 'change->submit-form#submitForm'],
                    'priority' => 3,
                ]);
            });

        $builder->addDependent(
            'supplierManufacturer',
            ['supplier', 'supplierCategory', 'supplierSubcategory'],
            function (
                DependentField $field,
                ?int $supplierId,
                ?int $supplierCategoryId,
                ?int $supplierSubcategoryId,
            ): void {
                $field->add(SupplierManufacturerIdType::class, [
                    'choices' => $this->supplierManufacturers->findSupplierManufacturers(
                        $supplierId,
                        $supplierCategoryId,
                        $supplierSubcategoryId
                    ),
                    'placeholder' => 'Any Manufacturer',
                    'priority' => 2,
                ]);
            });

        $builder->get('supplier')
            ->addModelTransformer($this->transformer->for(Supplier::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupplierProductSearchCriteria::class,
        ]);
    }
}
