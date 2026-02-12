<?php

namespace App\Purchasing\UI\Http\Form\Model;

use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Purchasing\Domain\Model\Supplier\SupplierColourScheme;
use Symfony\Component\Validator\Constraints as Assert;

final class SupplierForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a supplier name')]
    public ?string $name = null;

    public bool $isActive = false;

    public ?SupplierColourScheme $colourScheme = null;

    public static function fromEntity(Supplier $supplier): self
    {
        $form = new self();

        $form->id = $supplier->getPublicId()->value();
        $form->name = $supplier->getName();
        $form->isActive = $supplier->isActive();
        $form->colourScheme = $supplier->getColourSchemeEnum();

        return $form;
    }
}
