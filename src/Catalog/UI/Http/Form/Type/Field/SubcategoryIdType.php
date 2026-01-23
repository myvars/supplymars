<?php

namespace App\Catalog\UI\Http\Form\Type\Field;

use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Shared\UI\Form\DataTransformer\IdToEntityTransformerFactory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<int|null>
 */
final class SubcategoryIdType extends AbstractType
{
    public function __construct(private readonly IdToEntityTransformerFactory $transformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->transformer->for(Subcategory::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Defaults for convenience; can be overridden
            'class' => Subcategory::class,
            'choice_label' => 'name',
            'placeholder' => 'Choose a Subcategory',
            'property_path' => 'subcategoryId',
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return EntityType::class;
    }
}
