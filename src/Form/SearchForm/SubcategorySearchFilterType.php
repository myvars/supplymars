<?php

namespace App\Form\SearchForm;

use App\DTO\SearchDto\SubcategorySearchDto;
use App\Entity\Category;
use App\Entity\User;
use App\Enum\PriceModel;
use App\Form\DataTransformer\IdToCategoryTransformer;
use App\Form\DataTransformer\IdToManagerTransformer;
use App\Form\DataTransformer\stringToPriceModelTransformer;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubcategorySearchFilterType extends AbstractType
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly IdToCategoryTransformer $idToCategoryTransformer,
        private readonly IdToManagerTransformer $idToManagerTransformer,
        private readonly stringToPriceModelTransformer $stringToPriceModelTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('categoryId', EntityType::class, [
                'label' => 'Category',
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Category',
            ])
            ->add('priceModel', EnumType::class, [
                'class' => PriceModel::class,
                'choice_label' => fn (PriceModel $priceModel): string => $priceModel->getName(),
                'label' => 'Price Model',
                'placeholder' => 'Any Price Model',
            ])
            ->add('managerId', EntityType::class, [
                'label' => 'Subcategory Manager',
                'class' => User::class,
                'choices' => $this->entityManager->getRepository(User::class)->findBy(['isStaff' => true]),
                'choice_label' => 'fullName',
                'placeholder' => 'Any Manager',
            ])
            ->add('query', HiddenType::class)
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->get('categoryId')
            ->addModelTransformer($this->idToCategoryTransformer);
        $builder->get('priceModel')
            ->addModelTransformer($this->stringToPriceModelTransformer);
        $builder->get('managerId')
            ->addModelTransformer($this->idToManagerTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubcategorySearchDto::class,
        ]);
    }
}
