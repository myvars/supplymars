<?php

namespace App\Purchasing\UI\Http\Form\Model;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\UI\Http\Validation\ValidProductId;
use Symfony\Component\Validator\Constraints as Assert;

final class SupplierProductForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a supplier product name')]
    public ?string $name = null;

    #[Assert\NotBlank(message: 'Please enter a product code')]
    public ?string $productCode = null;

    #[Assert\NotNull(message: 'Please choose a Supplier')]
    public ?int $supplierId = null;

    #[Assert\NotNull(message: 'Please choose a Category')]
    public ?int $supplierCategoryId = null;

    #[Assert\NotNull(message: 'Please choose a Subcategory')]
    public ?int $supplierSubcategoryId = null;

    #[Assert\NotNull(message: 'Please choose a Manufacturer')]
    public ?int $supplierManufacturerId = null;

    #[Assert\NotBlank(message: 'Please enter a manufacturer part number')]
    public ?string $mfrPartNumber = null;

    #[Assert\NotNull(message: 'Please enter a weight')]
    #[Assert\Range(notInRangeMessage: 'Please enter a product weight(grams)', min: 0, max: 100000)]
    public ?int $weight = 0;

    #[Assert\NotNull(message: 'Please enter a stock level')]
    #[Assert\Range(notInRangeMessage: 'Please enter a stock level', min: 0, max: 10000)]
    public ?int $stock = 0;

    #[Assert\NotNull(message: 'Please enter a lead time')]
    #[Assert\Range(notInRangeMessage: 'Please enter a lead time(days)', min: 0, max: 1000)]
    public ?int $leadTimeDays = null;

    #[Assert\NotBlank(message: 'Please enter a cost')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero cost')]
    public ?string $cost = '0.00';

    #[ValidProductId]
    public ?int $productId = null;

    public bool $isActive = false;

    public static function fromEntity(SupplierProduct $entity): self
    {
        $form = new self();

        $form->id = $entity->getPublicId()->value();
        $form->name = $entity->getName();
        $form->productCode = $entity->getProductCode();
        $form->supplierId = $entity->getSupplier()->getId();
        $form->supplierCategoryId = $entity->getSupplierCategory()?->getId();
        $form->supplierSubcategoryId = $entity->getSupplierSubcategory()?->getId();
        $form->supplierManufacturerId = $entity->getSupplierManufacturer()?->getId();
        $form->mfrPartNumber = $entity->getMfrPartNumber();
        $form->weight = $entity->getWeight();
        $form->stock = $entity->getStock();
        $form->leadTimeDays = $entity->getLeadTimeDays();
        $form->cost = $entity->getCost();
        $form->productId = $entity->getProduct() instanceof Product ?
            $entity->getProduct()->getId() : null;
        $form->isActive = $entity->isActive();

        return $form;
    }
}
