<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Catalog\Domain\Model\Product\Product;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

final class DeleteSupplierProductFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testDeleteViaConfirmFlow(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['name' => 'To Be Deleted', 'product' => null]);
        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
            ->get('/supplier-product/' . $publicId . '/delete/confirm')
            ->click('Delete Supplier Product')
            ->assertOn('/supplier-product/')
            ->assertSee('Supplier Product deleted');
    }

    public function testDeletingActiveSourceMovesProductToNextBestSource(): void
    {
        $product = ProductFactory::createOne();
        $active = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 5]);
        $next = SupplierProductFactory::createOne(['product' => $product, 'cost' => '20.00', 'stock' => 5]);

        self::assertSame($active->getId(), $product->getActiveProductSource()?->getId());
        $priceBefore = $product->getSellPrice();

        $this->deleteViaConfirmFlow($active)
            ->assertSee('Supplier Product deleted');

        $product = $this->reload($product);
        self::assertSame($next->getId(), $product->getActiveProductSource()?->getId());
        self::assertSame('20.00', $product->getCost());
        $priceAfter = $product->getSellPrice();
        self::assertNotNull($priceBefore);
        self::assertNotNull($priceAfter);
        self::assertSame(1, bccomp($priceAfter, $priceBefore, 2));
    }

    public function testDeletingOnlySourceLeavesProductWithoutSource(): void
    {
        $product = ProductFactory::createOne();
        $only = SupplierProductFactory::createOne(['product' => $product]);

        self::assertTrue($product->hasActiveProductSource());

        $this->deleteViaConfirmFlow($only)
            ->assertSee('Supplier Product deleted');

        $product = $this->reload($product);
        self::assertFalse($product->hasActiveProductSource());
        self::assertSame(0, $product->getStock());
    }

    public function testDeletingSupplierProductOnPurchaseOrderIsRefused(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['product' => null]);
        PurchaseOrderItemFactory::createOne(['supplierProduct' => $supplierProduct]);

        $this->deleteViaConfirmFlow($supplierProduct)
            ->assertSee('Used on purchase orders');

        SupplierProductFactory::assert()->exists(['id' => $supplierProduct->getId()]);
    }

    public function testRefusedDeleteLeavesActiveSourceUnchanged(): void
    {
        $product = ProductFactory::createOne();
        $active = SupplierProductFactory::createOne(['product' => $product, 'cost' => '10.00', 'stock' => 5]);
        SupplierProductFactory::createOne(['product' => $product, 'cost' => '20.00', 'stock' => 5]);
        PurchaseOrderItemFactory::createOne(['supplierProduct' => $active]);
        $priceBefore = $product->getSellPrice();

        $this->deleteViaConfirmFlow($active)
            ->assertSee('Used on purchase orders');

        $product = $this->reload($product);
        self::assertSame($active->getId(), $product->getActiveProductSource()?->getId());
        self::assertSame($priceBefore, $product->getSellPrice());
    }

    private function deleteViaConfirmFlow(SupplierProduct $supplierProduct): KernelBrowser
    {
        return $this->browser()
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
            ->get('/supplier-product/' . $supplierProduct->getPublicId()->value() . '/delete/confirm')
            ->click('Delete Supplier Product')
            ->assertSuccessful();
    }

    private function reload(Product $product): Product
    {
        self::getContainer()->get('doctrine')->getManager()->clear();
        $product = ProductFactory::repository()->find($product->getId());
        self::assertInstanceOf(Product::class, $product);

        return $product;
    }
}
