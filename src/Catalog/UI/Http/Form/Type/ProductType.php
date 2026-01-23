<?php

namespace App\Catalog\UI\Http\Form\Type;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\UI\Http\Form\Model\ProductForm;
use App\Catalog\UI\Http\Form\Type\Field\SubcategoryIdType;
use App\Customer\Domain\Model\User\User;
use App\Shared\UI\Form\DataTransformer\IdToEntityTransformerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

/**
 * @extends AbstractType<ProductForm>
 */
final class ProductType extends AbstractType
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
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
            ->add('name', null, [
                'label' => 'Product Name',
                'priority' => 5,
            ])
            ->add('description', null, [
                'label' => 'Product Description',
                'priority' => 4,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Category',
                'attr' => ['data-action' => 'change->submit-form#submitForm'],
                'priority' => 3,
                'property_path' => 'categoryId',
            ])
            ->add('manufacturer', EntityType::class, [
                'class' => Manufacturer::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Manufacturer',
                'property_path' => 'manufacturerId',
            ])
            ->add('mfrPartNumber', null, [
                'label' => 'Manufacturer Part Number',
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'label' => 'Product Manager',
                'placeholder' => 'No product manager',
                'choices' => $this->em->getRepository(User::class)->findStaff(),
                'required' => false,
                'property_path' => 'ownerId',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
            ])
        ;

        // Submit used by DynamicFormBuilder to auto-refresh dependent subcategory
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
                'priority' => 2,
            ]);
        });

        $builder->get('category')
            ->addModelTransformer($this->transformer->for(Category::class));
        $builder->get('manufacturer')
            ->addModelTransformer($this->transformer->for(Manufacturer::class));
        $builder->get('owner')
            ->addModelTransformer($this->transformer->for(User::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ProductForm::class,
        ]);
    }
}
