<?php

namespace App\Tests\Pricing\Unit;

use App\Catalog\Domain\Model\Product\Product;
use App\Pricing\Infrastructure\Persistence\Doctrine\EventListener\ProductPriceUpdater;
use App\Service\Product\ProductPriceCalculator;
use Doctrine\ORM\Event\PreUpdateEventArgs;
use PHPUnit\Framework\TestCase;

class ProductPriceUpdaterTest extends TestCase
{
    public function testPreUpdateRecalculatesPriceWhenFieldsChange(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $product = $this->createMock(Product::class);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(fn($fieldName): bool => in_array($fieldName, ['cost', 'defaultMarkup', 'priceModel', 'subcategory', 'isActive']));

        $priceCalculatorMock->expects($this->once())
            ->method('recalculatePrice')
            ->with($product, false);

        $listener = new ProductPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($product, $eventArgsMock);
    }

    public function testPreUpdateSkipsRecalculationWhenNoRelevantFieldsChange(): void
    {
        $priceCalculatorMock = $this->createMock(ProductPriceCalculator::class);
        $eventArgsMock = $this->createMock(PreUpdateEventArgs::class);

        $product = $this->createMock(Product::class);

        $eventArgsMock->method('hasChangedField')
            ->willReturnCallback(fn($fieldName): bool => $fieldName == 'none');

        $priceCalculatorMock->expects($this->never())
            ->method('recalculatePrice');

        $listener = new ProductPriceUpdater($priceCalculatorMock);
        $listener->preUpdate($product, $eventArgsMock);
    }
}
