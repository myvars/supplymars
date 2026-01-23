<?php

namespace App\Pricing\UI\Http\Form\Model;

use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Shared\Domain\ValueObject\PriceModel;
use Symfony\Component\Validator\Constraints as Assert;

final class SubcategoryCostForm
{
    public ?string $id = null;

    /** @var numeric-string|null */
    #[Assert\NotBlank(message: 'Please enter a subcategory markup %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero subcategory markup %')]
    public ?string $defaultMarkup = Subcategory::DEFAULT_MARKUP;

    #[Assert\NotNull(message: 'Please choose a price model')]
    public ?PriceModel $priceModel = Subcategory::DEFAULT_PRICE_MODEL;

    public bool $isActive = false;

    public static function fromEntity(Subcategory $subcategory): self
    {
        $form = new self();

        $form->id = $subcategory->getPublicId()->value();
        $form->defaultMarkup = $subcategory->getDefaultMarkup();
        $form->priceModel = $subcategory->getPriceModel();
        $form->isActive = $subcategory->isActive();

        return $form;
    }
}
