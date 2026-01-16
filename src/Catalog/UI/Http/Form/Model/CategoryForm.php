<?php

namespace App\Catalog\UI\Http\Form\Model;

use App\Catalog\Domain\Model\Category\Category;
use App\Shared\Domain\ValueObject\PriceModel;
use Symfony\Component\Validator\Constraints as Assert;

final class CategoryForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a category name')]
    public ?string $name = null;

    #[Assert\NotNull(message: 'Please choose a VAT rate')]
    public ?int $vatRateId = null;

    #[Assert\NotBlank(message: 'Please enter a category markup %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero category markup %')]
    public ?string $defaultMarkup = Category::DEFAULT_MARKUP;

    #[Assert\NotNull(message: 'Please choose a price model')]
    #[Assert\NotEqualTo(value: PriceModel::NONE, message: 'A category must have a price model')]
    public ?PriceModel $priceModel = Category::DEFAULT_PRICE_MODEL;

    #[Assert\NotNull(message: 'Please choose a category owner')]
    public ?int $ownerId = null;

    public bool $isActive = false;

    public static function fromEntity(Category $category): self
    {
        $form = new self();

        $form->id = $category->getPublicId()->value();
        $form->name = $category->getName();
        $form->vatRateId = $category->getVatRate()?->getId();
        $form->defaultMarkup = $category->getDefaultMarkup();
        $form->priceModel = $category->getPriceModel();
        $form->ownerId = $category->getOwner()?->getId();
        $form->isActive = $category->isActive();

        return $form;
    }
}
