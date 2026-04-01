<?php

namespace App\Note\UI\Http\Form\Type;

use App\Note\Domain\Model\Message\MessageVisibility;
use App\Note\UI\Http\Form\Model\ReplyForm;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\ChoiceType;
use Symfony\Component\Form\Extension\Core\Type\TextareaType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ReplyForm>
 */
final class ReplyType extends AbstractType
{
    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('body', TextareaType::class, [
                'label' => 'Message',
                'attr' => ['rows' => 3, 'placeholder' => 'Write your reply...'],
                'required' => false,
            ])
            ->add('visibility', ChoiceType::class, [
                'label' => 'Visibility',
                'choices' => [
                    MessageVisibility::PUBLIC->getLabel() => MessageVisibility::PUBLIC->value,
                    MessageVisibility::INTERNAL->getLabel() => MessageVisibility::INTERNAL->value,
                ],
                'help' => 'Internal replies are only visible to staff.',
            ])
        ;
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReplyForm::class,
        ]);
    }
}
