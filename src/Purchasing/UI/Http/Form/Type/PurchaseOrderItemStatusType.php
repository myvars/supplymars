<?php

namespace App\Purchasing\UI\Http\Form\Type;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\UI\Http\Form\Model\PurchaseOrderItemStatusForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

final class PurchaseOrderItemStatusType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
            ->add('purchaseOrderItemStatus', EnumType::class, [
                'class' => PurchaseOrderStatus::class,
                'choice_label' => fn (PurchaseOrderStatus $status): string => $status->value,
                'label' => 'PO Item Status',
                'placeholder' => 'Choose a PO Item Status',
            ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PurchaseOrderItemStatusForm::class,
        ]);
    }
}
