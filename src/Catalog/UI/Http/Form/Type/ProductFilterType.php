<?php

namespace App\Catalog\UI\Http\Form\Type;

use App\Catalog\Application\Search\ProductSearchCriteria;
use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\UI\Http\Form\Type\Field\SubcategoryIdType;
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

/**
 * @extends AbstractType<ProductSearchCriteria>
 */
final class ProductFilterType extends AbstractType
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
            ->add('category', EntityType::class, [
                'label' => 'Category',
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Category',
                'attr' => ['data-action' => 'change->submit-form#submitForm'],
                'priority' => 2,
                'property_path' => 'categoryId',
            ])
            ->add('manufacturer', EntityType::class, [
                'label' => 'Manufacturer',
                'class' => Manufacturer::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Manufacturer',
                'property_path' => 'manufacturerId',
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
            'attr' => ['class' => 'hidden-submit-button', 'data-submit-form-target' => 'submit'],
        ]);

        // Dependent subcategory field filtered by selected category
        $builder->addDependent('subcategory', 'category', function (DependentField $field, ?int $categoryId): void {
            $category = $categoryId !== null
                ? $this->em->getRepository(Category::class)->find($categoryId)
                : null;
            $field->add(SubcategoryIdType::class, [
                'choices' => $category?->getSubcategories() ?? [],
                'priority' => 1,
            ]);
        });

        $builder->get('category')
            ->addModelTransformer($this->transformer->for(Category::class));
        $builder->get('manufacturer')
            ->addModelTransformer($this->transformer->for(Manufacturer::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductSearchCriteria::class,
        ]);
    }
}
