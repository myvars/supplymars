<?php

declare(strict_types=1);

namespace App\Note\UI\Http\Form\Type;

use App\Note\Application\Search\TicketSearchCriteria;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Model\Ticket\TicketStatus;
use App\Note\Domain\Repository\PoolRepository;
use App\Note\UI\Http\Form\DataTransformer\StringToTicketStatusTransformer;
use App\Shared\UI\Form\DataTransformer\IdToEntityTransformerFactory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<TicketSearchCriteria>
 */
final class TicketFilterType extends AbstractType
{
    public function __construct(
        private readonly IdToEntityTransformerFactory $transformer,
        private readonly PoolRepository $pools,
        private readonly StringToTicketStatusTransformer $statusTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pool', EntityType::class, [
                'class' => Pool::class,
                'choice_label' => 'name',
                'label' => 'Pool',
                'placeholder' => 'Any Pool',
                'choices' => $this->pools->findActive(),
                'property_path' => 'poolId',
                'required' => false,
            ])
            ->add('status', EnumType::class, [
                'class' => TicketStatus::class,
                'choice_label' => fn (TicketStatus $status): string => $status->getLabel(),
                'label' => 'Status',
                'placeholder' => 'Any Status',
                'required' => false,
            ])
            ->add('showSnoozed', CheckboxType::class, [
                'label' => 'Show Snoozed',
                'required' => false,
            ])
            ->add('query', HiddenType::class)
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->get('pool')
            ->addModelTransformer($this->transformer->for(Pool::class));
        $builder->get('status')
            ->addModelTransformer($this->statusTransformer);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => TicketSearchCriteria::class,
        ]);
    }
}
