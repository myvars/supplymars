<?php

declare(strict_types=1);

namespace App\Review\UI\Http\Form\Type;

use App\Review\Domain\Model\Review\RejectionReason;
use App\Review\UI\Http\Form\Model\RejectReviewForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\EnumType;
use Symfony\Component\Form\Extension\Core\Type\HiddenType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<RejectReviewForm>
 */
final class RejectReviewType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('id', HiddenType::class, [
                'required' => false,
            ])
            ->add('reason', EnumType::class, [
                'class' => RejectionReason::class,
                'label' => 'Rejection Reason',
                'choice_label' => fn (RejectionReason $reason): string => $reason->label(),
                'placeholder' => 'Select reason',
            ])
            ->add('notes', TextareaType::class, [
                'label' => 'Moderation Notes',
                'required' => false,
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => RejectReviewForm::class,
        ]);
    }
}
