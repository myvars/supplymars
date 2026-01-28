<?php

namespace App\Review\UI\Http\Form\Type;

use App\Review\Application\Search\ReviewSearchCriteria;
use App\Review\Domain\Model\Review\ReviewStatus;
use App\Review\UI\Http\Form\DataTransformer\StringToReviewStatusTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\IntegerType;
use Symfony\Component\Form\Extension\Core\Type\SubmitType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ReviewSearchCriteria>
 */
final class ReviewFilterType extends AbstractType
{
    public function __construct(
        private readonly StringToReviewStatusTransformer $stringToReviewStatusTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('reviewStatus', EnumType::class, [
                'class' => ReviewStatus::class,
                'label' => 'Status',
                'choice_label' => fn (ReviewStatus $status): string => $status->value,
                'placeholder' => 'Any Status',
                'required' => false,
            ])
            ->add('productId', IntegerType::class, [
                'label' => 'Product ID',
                'required' => false,
            ])
            ->add('customerId', IntegerType::class, [
                'label' => 'Customer ID',
                'required' => false,
            ])
            ->add('rating', ChoiceType::class, [
                'label' => 'Rating',
                'choices' => [
                    'Any' => null,
                    '1 Star' => 1,
                    '2 Stars' => 2,
                    '3 Stars' => 3,
                    '4 Stars' => 4,
                    '5 Stars' => 5,
                ],
                'required' => false,
            ])
            ->add('query', HiddenType::class)
            ->add('sort', HiddenType::class)
            ->add('sortDirection', HiddenType::class)
            ->add('page', HiddenType::class)
            ->add('limit', HiddenType::class)
        ;

        $builder->get('reviewStatus')
            ->addModelTransformer($this->stringToReviewStatusTransformer);

        $builder->add('auto-update', SubmitType::class, [
            'attr' => ['class' => 'hidden-submit-button', 'data-submit-form-target' => 'submit'],
        ]);
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReviewSearchCriteria::class,
        ]);
    }
}
