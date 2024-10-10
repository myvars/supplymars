<?php

namespace App\Form\SearchForm;

use App\DTO\SearchDto\PurchaseOrderSearchDto;
use App\Entity\Supplier;
use App\Enum\PurchaseOrderStatus;
use App\Form\DataTransformer\IdToSupplierTransformer;
use App\Form\DataTransformer\stringToPurchaseOrderStatusTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

class PurchaseOrderSearchFilterType extends AbstractType
{
    public function __construct(
        private readonly IdToSupplierTransformer $idToSupplierTransformer,
        private readonly stringToPurchaseOrderStatusTransformer $stringToPurchaseOrderStatusTransformer
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('supplierId', EntityType::class, [
                'label' => 'Supplier',
                'class' => Supplier::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Supplier',
            ])
            ->add('purchaseOrderId', null, [
                'label' => 'Purchase Order Id'
            ])
            ->add('customerOrderId', null, [
                'required' => false,
                'label' => 'Customer Order Id'
            ])
            ->add('customerId', null, [
                'label' => 'Customer Id'
            ])
            ->add('productId', null, [
                'label' => 'with Product Id'
            ])
            ->add('purchaseOrderStatus', EnumType::class, [
                'class' => PurchaseOrderStatus::class,
                'choice_label' => fn (PurchaseOrderStatus $purchaseOrderStatus): string => $purchaseOrderStatus->value,
                'label' => 'Purchase Order Status',
                'placeholder' => 'Any Purchase Order Status',
            ])
            ->add('query', HiddenType::class)
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->get('supplierId')
            ->addModelTransformer($this->idToSupplierTransformer);

        $builder->get('purchaseOrderStatus')
            ->addModelTransformer($this->stringToPurchaseOrderStatusTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PurchaseOrderSearchDto::class,
        ]);
    }
}
