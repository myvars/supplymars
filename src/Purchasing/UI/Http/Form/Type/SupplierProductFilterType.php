<?php

namespace App\Purchasing\UI\Http\Form\Type;

use App\Purchasing\Application\Search\SupplierProductSearchCriteria;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Purchasing\UI\Http\Form\Type\Field\SupplierCategoryIdType;
use App\Purchasing\UI\Http\Form\Type\Field\SupplierManufacturerIdType;
use App\Purchasing\UI\Http\Form\Type\Field\SupplierSubcategoryIdType;
use App\Shared\UI\Form\DataTransformer\IdToEntityTransformerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

final class SupplierProductFilterType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $em,
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
                $field->add(SupplierCategoryIdType::class, [
                    'choices' => $this->em->getRepository(SupplierCategory::class)->findBy(['supplier' => $supplierId]) ?? [],
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
                $field->add(SupplierSubcategoryIdType::class, [
                    'choices' => $this->em->getRepository(SupplierSubcategory::class)->findBy(['supplierCategory' => $supplierCategoryId]) ?? [],
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
                    'choices' => $this->em->getRepository(SupplierManufacturer::class)->findSupplierManufacturers(
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
