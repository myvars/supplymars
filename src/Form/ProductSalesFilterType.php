<?php

namespace App\Form;

use App\DTO\ProductSalesFilterDto;
use App\Entity\Category;
use App\Entity\Manufacturer;
use App\Entity\Subcategory;
use App\Entity\Supplier;
use App\Form\DataTransformer\IdToCategoryTransformer;
use App\Form\DataTransformer\IdToManufacturerTransformer;
use App\Form\DataTransformer\IdToSupplierTransformer;
use App\Form\SearchForm\CustomSubcategoryType;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class ProductSalesFilterType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IdToCategoryTransformer $idToCategoryTransformer,
        private readonly IdToManufacturerTransformer $idToManufacturerTransformer,
        private readonly IdToSupplierTransformer $idToSupplierTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('productId', null, [
                'label' => 'Product Id'
            ])
            ->add('customerId', null, [
                'label' => 'Customer Id'
            ])
            ->add('categoryId', EntityType::class, [
                'label' => 'Category',
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Category',
                'attr' => ['data-action' => 'change->submit-form#submitForm'],
                'priority' => 2,
            ])
            ->add('manufacturerId', EntityType::class, [
                'label' => 'Manufacturer',
                'class' => Manufacturer::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Manufacturer',
            ])
            ->add('supplierId', EntityType::class, [
                'label' => 'Supplier',
                'class' => Supplier::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Supplier',
            ])
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->add('auto-update', SubmitType::class, [
            'attr' => ['class' => 'hidden-submit-button', 'data-submit-form-target' => 'submit']
        ]);

        $builder->addDependent(
            'subcategoryId', 'categoryId', function(DependentField $field, ?int $categoryId): void {
            $field
                ->add(CustomSubcategoryType::class, [
                    'label' => 'Subcategory',
                    'class' => Subcategory::class,
                    'choices' => $this->entityManager->getRepository(Subcategory::class)
                        ->findBy(['category' => $categoryId]),
                    'choice_label' => 'name',
                    'placeholder' => 'Any Subcategory',
                    'priority' => 1,
                ]);
        });

        $builder->get('categoryId')
            ->addModelTransformer($this->idToCategoryTransformer);

        $builder->get('manufacturerId')
            ->addModelTransformer($this->idToManufacturerTransformer);

        $builder->get('supplierId')
            ->addModelTransformer($this->idToSupplierTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductSalesFilterDto::class,
        ]);
    }
}
