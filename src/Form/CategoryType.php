<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\User;
use App\Entity\VatRate;
use App\Enum\PriceModel;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class CategoryType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('name', null, [
                'label' => 'Category Name',
            ])
            ->add('vatRate', EntityType::class, [
                'class' => VatRate::class,
                'choice_label' => 'name',
                'label' => 'VAT Rate',
                'placeholder' => 'Choose a VAT Rate',
            ])
            ->add('defaultMarkup', PercentType::class, [
                'scale' => 3,
                'type' => 'integer',
                'label' => 'Category Markup %',
            ])
            ->add('priceModel', EnumType::class, [
                'class' => PriceModel::class,
                'choice_label' => fn (PriceModel $priceModel) => $priceModel->getName(),
                'label' => 'Price Model',
                'placeholder' => 'Choose a Price Model',
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'label' => 'Category Manager',
                'placeholder' => 'Choose a Category Manager',
            ])
            ->add('isActive', null, [
                'label' => 'Active',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => Category::class,
        ]);
    }
}
