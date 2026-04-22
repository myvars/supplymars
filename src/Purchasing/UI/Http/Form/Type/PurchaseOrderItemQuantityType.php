<?php

declare(strict_types=1);

namespace App\Purchasing\UI\Http\Form\Type;

use App\Purchasing\UI\Http\Form\Model\PurchaseOrderItemQuantityForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PurchaseOrderItemQuantityForm>
 */
final class PurchaseOrderItemQuantityType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
            ->add('quantity');
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PurchaseOrderItemQuantityForm::class,
        ]);
    }
}
