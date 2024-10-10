<?php

namespace App\Form\SearchForm;

use App\DTO\SearchDto\SubcategorySearchDto;
use App\Entity\Category;
use App\Form\DataTransformer\IdToCategoryTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubcategorySearchFilterType extends AbstractType
{
    public function __construct(private readonly IdToCategoryTransformer $idToCategoryTransformer)
    {
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
            ->add('query', HiddenType::class)
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->get('categoryId')
            ->addModelTransformer($this->idToCategoryTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => SubcategorySearchDto::class,
        ]);
    }
}
