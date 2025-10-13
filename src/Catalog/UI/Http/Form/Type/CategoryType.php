<?php

namespace App\Catalog\UI\Http\Form\Type;

use App\Catalog\UI\Http\Form\Model\CategoryForm;
use App\Customer\Domain\Model\User\User;
use App\Pricing\Domain\Model\VatRate\VatRate;
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

final class CategoryType extends AbstractType
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
                'required' => false
            ])
            ->add('name', null, [
                'label' => 'Category Name'
            ])
            ->add('vatRate', EntityType::class, [
                'class' => VatRate::class,
                'choice_label' => 'name',
                'label' => 'VAT Rate',
                'placeholder' => 'Choose a VAT Rate',
                'property_path' => 'vatRateId',
            ])
            ->add('defaultMarkup', PercentType::class, [
                'scale' => 3,
                'type' => 'integer',
                'label' => 'Category Markup %',
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
                'label' => 'Category Manager',
                'placeholder' => 'Choose a Category Manager',
                'choices' => $this->em->getRepository(User::class)->findStaff(),
                'property_path' => 'ownerId',
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
            ])
        ;

        $builder->get('vatRate')
            ->addModelTransformer($this->transformer->for(VatRate::class));
        $builder->get('owner')
            ->addModelTransformer($this->transformer->for(User::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => CategoryForm::class,
        ]);
    }
}
