<?php

namespace App\Form\SearchForm;

use App\Form\DataTransformer\IdToSubcategoryTransformer;
use Symfony\Bridge\Doctrine\Form\Type\EntityType;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\FormBuilderInterface;

class CustomSubcategoryType extends AbstractType
{
    public function __construct(private readonly IdToSubcategoryTransformer $idToSubcategoryTransformer)
    {
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        $builder->addModelTransformer($this->idToSubcategoryTransformer);
    }

    #[\Override]
    public function getParent(): string
    {
        return EntityType::class;
    }
}
