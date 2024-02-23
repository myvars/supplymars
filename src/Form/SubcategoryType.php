<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\PriceModel;
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
            ->add('defaultMarkup', PercentType::class, [
                'scale' => 3,
                'type' => 'integer',
                'label' => 'Subcategory Markup %',
            ])
            ->add('priceModel', EntityType::class, [
                'class' => PriceModel::class,
                'choice_label' => 'name',
                'label' => 'Price Model',
                'placeholder' => 'Choose a Price Model',
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
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Subcategory::class,
        ]);
    }
}
