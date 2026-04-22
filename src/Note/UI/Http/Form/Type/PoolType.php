<?php

declare(strict_types=1);

namespace App\Note\UI\Http\Form\Type;

use App\Note\UI\Http\Form\Model\PoolForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PoolForm>
 */
final class PoolType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
            ->add('name', null, [
                'label' => 'Pool Name',
            ])
            ->add('description', TextareaType::class, [
                'label' => 'Description',
                'required' => false,
                'attr' => ['rows' => 3],
            ])
            ->add('isActive', CheckboxType::class, [
                'label' => 'Active',
                'required' => false,
            ])
            ->add('isCustomerVisible', CheckboxType::class, [
                'label' => 'Customer Visible',
                'required' => false,
                'help' => 'Customers can see and submit tickets to this pool.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PoolForm::class,
        ]);
    }
}
