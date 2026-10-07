<?php

namespace App\Order\UI\Http\Form\Type;

use App\Order\Application\Search\OrderSearchCriteria;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Order\UI\Http\Form\DataTransformer\stringToOrderStatusTransformer;
use App\Shared\UI\Http\Form\Type\DatePickerType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<OrderSearchCriteria>
 */
final class OrderFilterType extends AbstractType
{
    public function __construct(private readonly stringToOrderStatusTransformer $stringToOrderStatusTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('orderId', null, [
                'required' => false,
                'label' => 'Customer Order Id',
            ])
            ->add('purchaseOrderId', null, [
                'label' => 'Purchase Order Id',
            ])
            ->add('customerId', null, [
                'label' => 'Customer Id',
            ])
            ->add('productId', null, [
                'label' => 'with Product Id',
            ])
            ->add('orderStatus', EnumType::class, [
                'class' => OrderStatus::class,
                'choice_label' => fn (OrderStatus $orderStatus): string => $orderStatus->value,
                'label' => 'Order Status',
                'placeholder' => 'Any Order Status',
            ])
            ->add('startDate', DatePickerType::class, [
                'label' => 'Start Date',
                'required' => false,
                'side' => 'top',
                'attr' => [
                    'placeholder' => 'Any date',
                ],
            ])
            ->add('endDate', DatePickerType::class, [
                'label' => 'End Date',
                'required' => false,
                'side' => 'top',
                'attr' => [
                    'placeholder' => 'Any date',
                ],
            ])
            ->add('query', HiddenType::class)
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->get('orderStatus')
            ->addModelTransformer($this->stringToOrderStatusTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => OrderSearchCriteria::class,
        ]);
    }
}
