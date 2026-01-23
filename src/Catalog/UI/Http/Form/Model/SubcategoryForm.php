<?php

namespace App\Catalog\UI\Http\Form\Model;

use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Shared\Domain\ValueObject\PriceModel;
use Symfony\Component\Validator\Constraints as Assert;

final class SubcategoryForm
{
    public ?string $id = null;

    #[Assert\NotNull(message: 'Please choose a Category')]
    public ?int $categoryId = null;

    #[Assert\NotBlank(message: 'Please enter a subcategory name')]
    public ?string $name = null;

    /** @var numeric-string|null */
    #[Assert\NotBlank(message: 'Please enter a subcategory markup %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero subcategory markup %')]
    public ?string $defaultMarkup = Subcategory::DEFAULT_MARKUP;

    #[Assert\NotNull(message: 'Please choose a price model')]
    public ?PriceModel $priceModel = Subcategory::DEFAULT_PRICE_MODEL;

    public ?int $ownerId = null;

    public bool $isActive = false;

    public static function fromEntity(Subcategory $subcategory): self
    {
        $form = new self();

        $form->id = $subcategory->getPublicId()->value();
        $form->categoryId = $subcategory->getCategory()->getId();
        $form->name = $subcategory->getName();
        $form->defaultMarkup = $subcategory->getDefaultMarkup();
        $form->priceModel = $subcategory->getPriceModel();
        $form->ownerId = $subcategory->getOwner()?->getId();
        $form->isActive = $subcategory->isActive();

        return $form;
    }
}
