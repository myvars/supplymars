<?php

namespace App\Catalog\UI\Http\Form\Type;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\UI\Http\Form\Model\SubcategoryForm;
use App\Customer\Domain\Model\User\User;
use App\Shared\Domain\ValueObject\PriceModel;
use App\Shared\UI\Form\DataTransformer\IdToEntityTransformerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<SubcategoryForm>
 */
final class SubcategoryType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $em,
        private readonly IdToEntityTransformerFactory $transformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Category',
                'property_path' => 'categoryId',
            ])
            ->add('name', null, [
                'label' => 'Subcategory Name',
            ])
            ->add('defaultMarkup', PercentType::class, [
                'scale' => 3,
                'type' => 'integer',
                'label' => 'Subcategory Markup %',
                'help' => 'Overrides the category markup when set.',
            ])
            ->add('priceModel', EnumType::class, [
                'class' => PriceModel::class,
                'choice_label' => fn (PriceModel $priceModel): string => $priceModel->getName(),
                'label' => 'Price Model',
                'placeholder' => 'Choose a Price Model',
                'help' => 'Controls how sell prices are rounded. Use None to inherit from the category.',
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'label' => 'Subcategory Manager',
                'placeholder' => 'No subcategory manager',
                'choices' => $this->em->getRepository(User::class)->findStaff(),
                'property_path' => 'ownerId',
                'required' => false,
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
            ])
        ;
        $builder->get('category')
            ->addModelTransformer($this->transformer->for(Category::class));
        $builder->get('owner')
            ->addModelTransformer($this->transformer->for(User::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubcategoryForm::class,
        ]);
    }
}
