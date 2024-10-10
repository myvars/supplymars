<?php

namespace App\Form\SearchForm;

use App\DTO\SearchDto\SupplierProductSearchDto;
use App\Entity\Supplier;
use App\Entity\SupplierCategory;
use App\Entity\SupplierManufacturer;
use App\Entity\SupplierSubcategory;
use App\Form\DataTransformer\IdToSupplierTransformer;
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

class SupplierProductSearchFilterType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface  $entityManager,
        private readonly IdToSupplierTransformer $idToSupplierTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('supplierId', EntityType::class, [
                'label' => 'Supplier',
                'class' => Supplier::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Supplier',
                'attr' => ['data-action' => 'change->submit-form#submitForm'],
                'priority' => 5,
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
            'attr' => ['class' => 'hidden-submit-button', 'data-submit-form-target' => 'submit']
        ]);

        $builder->addDependent(
            'supplierCategoryId', 'supplierId', function(DependentField $field, ?int $supplierId): void {
            $field
                ->add(CustomSupplierCategoryType::class, [
                    'label' => 'Supplier Category',
                    'class' => SupplierCategory::class,
                    'choices' => $this->entityManager->getRepository(SupplierCategory::class)
                        ->findBy(['supplier' => $supplierId]),
                    'choice_label' => 'name',
                    'placeholder' => 'Any Category',
                    'attr' => ['data-action' => 'change->submit-form#submitForm'],
                    'priority' => 4,
                ]);
        });

        $builder->addDependent(
            'supplierSubcategoryId', 'supplierCategoryId', function(DependentField $field, ?int $supplierCategoryId): void {
            $field
                ->add(CustomSupplierSubcategoryType::class, [
                    'label' => 'Supplier Subcategory',
                    'class' => SupplierSubcategory::class,
                    'choices' => $this->entityManager->getRepository(SupplierSubcategory::class)
                        ->findBy(['supplierCategory' => $supplierCategoryId]),
                    'choice_label' => 'name',
                    'placeholder' => 'Any Subcategory',
                    'attr' => ['data-action' => 'change->submit-form#submitForm'],
                    'priority' => 3,
                ]);
        });

        $builder->addDependent(
            'supplierManufacturerId',
            ['supplierId', 'supplierCategoryId', 'supplierSubcategoryId'],
            function(
                DependentField $field,
                ?int $supplierId,
                ?int $supplierCategoryId,
                ?int $supplierSubcategoryId
            ): void {
                $field
                ->add(CustomSupplierManufacturerType::class, [
                    'label' => 'Supplier Manufacturer',
                    'class' => SupplierManufacturer::class,
                    'choices' => $this->entityManager->getRepository(SupplierManufacturer::class)
                        ->findSupplierManufacturers($supplierId, $supplierCategoryId, $supplierSubcategoryId),
                    'choice_label' => 'name',
                    'placeholder' => 'Any Manufacturer',
                    'priority' => 2,
                ]
            );
        });

        $builder->get('supplierId')
            ->addModelTransformer($this->idToSupplierTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SupplierProductSearchDto::class,
        ]);
    }
}
