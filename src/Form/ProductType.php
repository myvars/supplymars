<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Manufacturer;
use App\Entity\Product;
use App\Entity\Subcategory;
use App\Entity\User;
use App\Enum\PriceModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\MoneyType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfonycasts\DynamicForms\DependentField;
use Symfonycasts\DynamicForms\DynamicFormBuilder;

class ProductType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder = new DynamicFormBuilder($builder);

        $builder
            ->add('name', null, [
                'label' => 'Product Name',
                'row_attr' => ['class' => 'sm:col-span-2 mb-4'],
                'priority' => 4,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Category',
                'attr' => ['data-action' => 'change->submit-form#submitForm'],
                'priority' => 3,
            ])
            ->add('leadTimeDays', null, [
                'label' => 'Lead Time (days)',
                'priority' => 2,
            ])
            ->add('weight', null, [
                'label' => 'Weight (grams)',
            ])
            ->add('manufacturer', EntityType::class, [
                'class' => Manufacturer::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Manufacturer',
            ])
            ->add('priceModel', EnumType::class, [
                'class' => PriceModel::class,
                'choice_label' => fn (PriceModel $priceModel): string => $priceModel->getName(),
                'label' => 'Price Model',
                'placeholder' => 'Choose a Price Model',
            ])
            ->add('mfrPartNumber', null, [
                'label' => 'Manufacturer Part Number',
            ])
            ->add('cost', MoneyType::class, [
                'currency' => 'GBP',
                'label' => 'Cost',
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'label' => 'Product Manager',
                'placeholder' => 'No product manager',
                'choices' => $this->entityManager->getRepository(User::class)->findStaff(),
            ])
            ->add('defaultMarkup', PercentType::class, [
                'scale' => 3,
                'type' => 'integer',
                'label' => 'Product Markup %',
            ])
            ->add('isActive', null, [
                'label' => 'Active',
                'row_attr' => ['class' => 'sm:col-span-2 mb-4'],
            ])
        ;

        $builder->add('auto-update', SubmitType::class, [
            'attr' => ['class' => 'hidden-submit-button', 'data-submit-form-target' => 'submit']
        ]);

        $builder->addDependent('subcategory', 'category', function(DependentField $field, ?Category $category): void {
            $field
                ->add(EntityType::class, [
                    'class' => Subcategory::class,
                    'choices' => $category instanceof Category ? $category->getSubcategories() : [],
                    'choice_label' => 'name',
                    'placeholder' => 'Choose a Subcategory',
                    'priority' => 1,
                ]);
        });
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Product::class,
        ]);
    }
}
