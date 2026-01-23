<?php

namespace App\Purchasing\UI\Http\Form\Type\Field;

use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Shared\UI\Form\DataTransformer\IdToEntityTransformerFactory;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\OptionsResolver\OptionsResolver;

/**
 * @extends AbstractType<int|null>
 */
final class SupplierManufacturerIdType extends AbstractType
{
    public function __construct(private readonly IdToEntityTransformerFactory $transformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        // Attach on the builder (not on a Form instance)
        $builder->addModelTransformer($this->transformer->for(SupplierManufacturer::class));
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            // Defaults for convenience; can be overridden
            'class' => SupplierManufacturer::class,
            'choice_label' => 'name',
            'placeholder' => 'Choose a Manufacturer',
            'property_path' => 'supplierManufacturerId',
        ]);
    }

    #[\Override]
    public function getParent(): string
    {
        return EntityType::class;
    }
}
