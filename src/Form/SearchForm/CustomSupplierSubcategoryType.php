<?php

namespace App\Form\SearchForm;

use App\Form\DataTransformer\IdToSupplierSubcategoryTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomSupplierSubcategoryType extends AbstractType
{
    public function __construct(
        private readonly IdToSupplierSubcategoryTransformer $idToSupplierSubcategoryTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->idToSupplierSubcategoryTransformer);
    }

    #[\Override]
    public function getParent(): string
    {
        return EntityType::class;
    }
}
