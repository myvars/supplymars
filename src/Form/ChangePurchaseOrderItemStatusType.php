<?php

namespace App\Form;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Enum\PurchaseOrderStatus;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class ChangePurchaseOrderItemStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('purchaseOrderItemStatus', EnumType::class, [
                'class' => PurchaseOrderStatus::class,
                'choice_label' => fn (PurchaseOrderStatus $purchaseOrderItemStatus) => $purchaseOrderItemStatus->value,
                'label' => 'PO Item Status',
                'placeholder' => 'Choose a PO Item status',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ChangePurchaseOrderItemStatusDto::class,
        ]);
    }
}
