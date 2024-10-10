<?php

namespace App\Form\SearchForm;

use App\DTO\SearchDto\ProductSearchDto;
use App\Entity\Category;
use App\Entity\Manufacturer;
use App\Entity\Subcategory;
use App\Form\DataTransformer\IdToCategoryTransformer;
use App\Form\DataTransformer\IdToManufacturerTransformer;
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

class ProductSearchFilterType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IdToCategoryTransformer $idToCategoryTransformer,
        private readonly IdToManufacturerTransformer $idToManufacturerTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
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
            ->add('mfrPartNumber', null, [
                'label' => 'Manufacturer Part Number',
            ])
            ->add('inStock', ChoiceType::class, [
                'label' => 'Stock Level',
                'choices' => [
                    'Any' => null,
                    'In Stock' => true,
                    'Out of Stock' => false,
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductSearchDto::class,
        ]);
    }
}
