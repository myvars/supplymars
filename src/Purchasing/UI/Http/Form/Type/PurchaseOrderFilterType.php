<?php

namespace App\Purchasing\UI\Http\Form\Type;

use App\Purchasing\Application\Search\PurchaseOrderSearchCriteria;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\UI\Http\Form\DataTransformer\stringToPurchaseOrderStatusTransformer;
use App\Shared\UI\Form\DataTransformer\IdToEntityTransformerFactory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<PurchaseOrderSearchCriteria>
 */
final class PurchaseOrderFilterType extends AbstractType
{
    public function __construct(
        private readonly stringToPurchaseOrderStatusTransformer $stringToPurchaseOrderStatusTransformer,
        private readonly IdToEntityTransformerFactory $transformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('supplier', EntityType::class, [
                'label' => 'Supplier',
                'class' => Supplier::class,
                'choice_label' => 'name',
                'placeholder' => 'Any Supplier',
                'property_path' => 'supplierId',
            ])
            ->add('purchaseOrderId', null, [
                'label' => 'Purchase Order Id',
            ])
            ->add('orderId', null, [
                'required' => false,
                'label' => 'Customer Order Id',
            ])
            ->add('customerId', null, [
                'label' => 'Customer Id',
            ])
            ->add('productId', null, [
                'label' => 'with Product Id',
            ])
            ->add('purchaseOrderStatus', EnumType::class, [
                'class' => PurchaseOrderStatus::class,
                'choice_label' => fn (PurchaseOrderStatus $purchaseOrderStatus): string => $purchaseOrderStatus->value,
                'label' => 'Purchase Order Status',
                'placeholder' => 'Any Purchase Order Status',
            ])
            ->add('startDate', TextType::class, [
                'label' => 'Start Date',
                'required' => false,
                'attr' => [
                    'data-controller' => 'datepicker',
                    'placeholder' => 'yyyy-mm-dd',
                ],
            ])
            ->add('endDate', TextType::class, [
                'label' => 'End Date',
                'required' => false,
                'attr' => [
                    'data-controller' => 'datepicker',
                    'placeholder' => 'yyyy-mm-dd',
                ],
            ])
            ->add('query', HiddenType::class)
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->get('supplier')
            ->addModelTransformer($this->transformer->for(Supplier::class));
        $builder->get('purchaseOrderStatus')
            ->addModelTransformer($this->stringToPurchaseOrderStatusTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => PurchaseOrderSearchCriteria::class,
        ]);
    }
}
