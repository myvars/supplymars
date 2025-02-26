<?php

namespace App\Form\SearchForm;

use App\Form\DataTransformer\IdToSupplierCategoryTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomSupplierCategoryType extends AbstractType
{
    public function __construct(
        private readonly IdToSupplierCategoryTransformer $idToSupplierCategoryTransformer,
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->idToSupplierCategoryTransformer);
    }

    #[\Override]
    public function getParent(): string
    {
        return EntityType::class;
    }
}
