<?php

namespace App\Pricing\UI\Http\Form\Model;

use App\Catalog\Domain\Model\Product\Product;
use App\Shared\Domain\ValueObject\PriceModel;
use Symfony\Component\Validator\Constraints as Assert;

final class ProductCostForm
{
    public ?string $id = null;

    #[Assert\NotBlank(message: 'Please enter a product markup %')]
    #[Assert\PositiveOrZero(message: 'Please enter a positive or zero product markup %')]
    public ?string $defaultMarkup = Product::DEFAULT_MARKUP;

    #[Assert\NotNull(message: 'Please choose a price model')]
    public ?PriceModel $priceModel = Product::DEFAULT_PRICE_MODEL;

    public bool $isActive = false;

    public static function fromEntity(Product $product): self
    {
        $form = new self();

        $form->id = $product->getPublicId()->value();
        $form->defaultMarkup = $product->getDefaultMarkup();
        $form->priceModel = $product->getPriceModel();
        $form->isActive = $product->isActive();

        return $form;
    }
}
