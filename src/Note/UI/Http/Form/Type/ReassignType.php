<?php

declare(strict_types=1);

namespace App\Note\UI\Http\Form\Type;

use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Repository\PoolRepository;
use App\Note\UI\Http\Form\Model\ReassignForm;
use App\Shared\UI\Form\DataTransformer\IdToEntityTransformerFactory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<ReassignForm>
 */
final class ReassignType extends AbstractType
{
    public function __construct(
        private readonly IdToEntityTransformerFactory $transformer,
        private readonly PoolRepository $pools,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder
            ->add('pool', EntityType::class, [
                'class' => Pool::class,
                'choice_label' => 'name',
                'label' => 'New Pool',
                'placeholder' => 'Choose a Pool',
                'choices' => $this->pools->findActive(),
                'property_path' => 'poolId',
            ])
        ;

        $builder->get('pool')
            ->addModelTransformer($this->transformer->for(Pool::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => ReassignForm::class,
        ]);
    }
}
