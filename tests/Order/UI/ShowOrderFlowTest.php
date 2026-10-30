<?php

declare(strict_types=1);

namespace App\Tests\Order\UI;

use App\Order\Domain\Model\Order\OrderStatus;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\WithStory;

final class ShowOrderFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testShowPageRendersBreadcrumbAndDescriptionList(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);
        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId)
            ->assertSuccessful()
            ->assertSee('Orders')
            ->assertSee('Order #')
            ->assertSeeElement('nav[aria-label="breadcrumb"]')
            ->assertSeeElement('dl');
    }

    public function testPendingOrderOffersCancel(): void
    {
        $order = CustomerOrderFactory::createOne();
        CustomerOrderItemFactory::createOne(['customerOrder' => $order]);
        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId)
            ->assertSuccessful()
            ->assertSeeElement('a[href="/order/' . $publicId . '/cancel/confirm"]');
    }

    public function testItemCardShowsProductNumberAsOnProductPages(): void
    {
        $order = CustomerOrderFactory::createOne();
        $item = CustomerOrderItemFactory::createOne(['customerOrder' => $order]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $order->getPublicId()->value())
            ->assertSuccessful()
            // The card's own span: the page also shows "Order #" with the order's id
            ->assertContains('>#' . sprintf('%06d', $item->getProduct()->getId()) . '</span>');
    }

    // The status change it causes is logged against the current user
    #[WithStory(StaffUserStory::class)]
    public function testProcessingOrderDoesNotOfferCancel(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();
        PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'product' => $product, 'quantity' => 10]),
            'supplierProduct' => SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => $product, 'stock' => 100]),
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $order,
            'quantity' => 3,
        ]);
        self::assertSame(OrderStatus::PROCESSING, $order->getStatus());
        $publicId = $order->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/' . $publicId)
            ->assertSuccessful()
            ->assertSee('Allocate')
            ->assertNotSeeElement('a[href="/order/' . $publicId . '/cancel/confirm"]');
    }
}
