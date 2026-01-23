<?php

namespace App\Pricing\UI\Http\Form\Model;

use App\Catalog\Domain\Model\Category\Category;
use App\Shared\Domain\ValueObject\PriceModel;
use Symfony\Component\Validator\Constraints as Assert;

final class CategoryCostForm
{
    public ?string $id = null;

    /** @var numeric-string|null */
    #[Assert\NotBlank(message: 'Please enter a category markup %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero category markup %')]
    public ?string $defaultMarkup = Category::DEFAULT_MARKUP;

    #[Assert\NotNull(message: 'Please choose a price model')]
    #[Assert\NotEqualTo(value: PriceModel::NONE, message: 'A category must have a price model')]
    public ?PriceModel $priceModel = Category::DEFAULT_PRICE_MODEL;

    public bool $isActive = true;

    public static function fromEntity(Category $category): self
    {
        $form = new self();

        $form->id = $category->getPublicId()->value();
        $form->defaultMarkup = $category->getDefaultMarkup();
        $form->priceModel = $category->getPriceModel();
        $form->isActive = $category->isActive();

        return $form;
    }
}
