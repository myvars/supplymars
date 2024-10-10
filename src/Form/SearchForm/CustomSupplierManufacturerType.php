<?php

namespace App\Form\SearchForm;

use App\Form\DataTransformer\IdToSupplierManufacturerTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomSupplierManufacturerType extends AbstractType
{
    public function __construct(
        private readonly IdToSupplierManufacturerTransformer $idToSupplierManufacturerTransformer
    ) {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->idToSupplierManufacturerTransformer);
    }

    public function getParent()
    {
        return EntityType::class;
    }
}