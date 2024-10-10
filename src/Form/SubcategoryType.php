<?php

namespace App\Form;

use App\Entity\Category;
use App\Entity\Subcategory;
use App\Entity\User;
use App\Enum\PriceModel;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\PercentType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class SubcategoryType extends AbstractType
{
    public function __construct(private readonly EntityManagerInterface $entityManager)
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
            ->add('defaultMarkup', PercentType::class, [
                'scale' => 3,
                'type' => 'integer',
                'label' => 'Subcategory Markup %',
            ])
            ->add('priceModel', EnumType::class, [
                'class' => PriceModel::class,
                'choice_label' => fn (PriceModel $priceModel): string => $priceModel->getName(),
                'label' => 'Price Model',
                'placeholder' => 'Choose a Price Model',
            ])
            ->add('owner', EntityType::class, [
                'class' => User::class,
                'choice_label' => 'fullName',
                'label' => 'Subcategory Manager',
                'placeholder' => 'No subcategory manager',
                'choices' => $this->entityManager->getRepository(User::class)->findStaff(),
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
