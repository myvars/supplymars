<?php

namespace App\Pricing\Application\Handler;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Pricing\Application\Command\UpdateProductCost;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdateProductCostHandler
{
    public function __construct(
        private ProductRepository $products,
        private MarkupCalculator $markupCalculator,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(UpdateProductCost $command): Result
    {
        $product= $this->products->getByPublicId($command->id);
        if (!$product instanceof Product) {
            return Result::fail('Product not found.');
        }

        $product->changePricing(
            markupCalculator: $this->markupCalculator,
            defaultMarkup: $command->defaultMarkup,
            priceModel: $command->priceModel,
            isActive: $command->isActive,
        );

        $errors = $this->validator->validate($product);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('Product cost updated');
    }
}

