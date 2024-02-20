<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Subcategory;
use App\Entity\User;
use App\Entity\VatRate;
use App\Form\DataTransformer\IntegerToPercentageTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubcategoryType extends AbstractType
{
    public function __construct(private readonly IntegerToPercentageTransformer $transformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('category', EntityType::class, [
                'class' => Category::class,
                'choice_label' => 'name',
                'placeholder' => 'Choose a Category',
            ])
            ->add('name', null, [
                'label' => 'Subcategory Name',
            ])
            ->add('markup', PercentType::class, [
                'scale' => 2,
                'type' => 'integer',
                'label' => 'Markup %',
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'label' => 'Subcategory Manager',
                'placeholder' => 'Choose a Subcategory Manager',
            ])
            ->add('isActive', null, [
                'label' => 'Active',
            ])
        ;

        $builder->get('markup')->addModelTransformer($this->transformer);

    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subcategory::class,
        ]);
    }
}
