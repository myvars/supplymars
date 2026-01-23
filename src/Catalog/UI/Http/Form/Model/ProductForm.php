<?php

namespace App\Catalog\UI\Http\Form\Model;

use App\Catalog\Domain\Model\Product\Product;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a product name')]
    public ?string $name = null;

    public ?string $description = null;

    #[Assert\NotNull(message: 'Please choose a Category')]
    public ?int $categoryId = null;

    #[Assert\NotNull(message: 'Please choose a Subcategory')]
    public ?int $subcategoryId = null;

    #[Assert\NotNull(message: 'Please choose a Manufacturer')]
    public ?int $manufacturerId = null;

    #[Assert\NotBlank(message: 'Please enter a manufacturer part number')]
    public ?string $mfrPartNumber = null;

    public ?int $ownerId = null;

    public bool $isActive = false;

    public static function fromEntity(Product $product): self
    {
        $form = new self();

        $form->id = $product->getPublicId()->value();
        $form->name = $product->getName();
        $form->description = $product->getDescription();
        $form->categoryId = $product->getCategory()->getId();
        $form->subcategoryId = $product->getSubcategory()->getId();
        $form->manufacturerId = $product->getManufacturer()->getId();
        $form->mfrPartNumber = $product->getMfrPartNumber();
        $form->ownerId = $product->getOwner()?->getId();
        $form->isActive = $product->isActive();

        return $form;
    }
}
