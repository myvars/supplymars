<?php

namespace App\Catalog\UI\Http\Form\Model;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use Symfony\Component\Validator\Constraints as Assert;

final class ManufacturerForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a manufacturer name')]
    public ?string $name = null;

    public bool $isActive = false;

    public static function fromEntity(Manufacturer $manufacturer): self
    {
        $form = new self();

        $form->id = $manufacturer->getPublicId()->value();
        $form->name = $manufacturer->getName();
        $form->isActive = $manufacturer->isActive();

        return $form;
    }
}
