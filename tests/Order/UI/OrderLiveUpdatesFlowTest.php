<?php

declare(strict_types=1);

namespace App\Tests\Order\UI;

use App\Order\Application\Message\RefreshOrderPage;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Shared\Infrastructure\Notification\MercureLiveUpdatePublisher;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Mercure\MercureCookie;
use App\Tests\Shared\Mercure\QueuedNotifications;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\Exception\RuntimeException;
use Symfony\Component\Mercure\Update;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * Everyone with an order's page open sees it change (RefreshOrderPageForViewers). Each change is made as a caller
 * would, the queue is then handled as the worker would, and the test reads what reached the hub. Redirects are
 * not followed: the next request would start a new kernel, with an empty queue and hub.
 */
#[WithStory(StaffUserStory::class)]
final class OrderLiveUpdatesFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testOrderPageSubscribesToTheOrderAndItsViewersToasts(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $order = CustomerOrderFactory::createOne();
        $topic = 'order/' . $order->getPublicId()->value();

        $browser = $this->browser()
            ->actingAs($user)
            ->visit('/order/' . $order->getPublicId()->value())
            ->assertSuccessful()
            ->assertElementCount('#user-notifications-' . $user->getPublicId() . ' turbo-mercure-stream-source', 1)
            // Private, so the browser sends its cookie, without which the hub refuses it. Not permanent, and inside the
            // frame a visit replaces, so it goes with the page
            ->assertElementCount('turbo-frame#body turbo-mercure-stream-source[private][src$="?match=' . rawurlencode($topic) . '"]', 1)
            ->assertElementCount('turbo-mercure-stream-source', 2)
            // A live refresh's morph leaves an open sidebar menu, help panel or user menu alone (live-open)
            ->assertElementCount('[data-slot="sheet"][data-controller~="dialog"][data-controller~="live-open"] dialog#sheet-menu', 1)
            ->assertElementCount('[data-slot="sheet"][data-controller~="help-drawer"][data-controller~="live-open"] dialog#sheet-help turbo-frame#help', 1)
            ->assertElementCount('[data-slot="dropdown-menu"][data-controller~="dropdown-menu"][data-controller~="live-open"] [data-dropdown-menu-target="trigger"]', 1)
            // ...and an open modal (basic-modal), and the toaster (live-toaster)
            ->assertElementCount('[data-controller="basic-modal"][data-action*="basic-modal#keepScrollLock"]', 1)
            ->assertElementCount('div[data-controller="live-toaster"] #flash-container', 1)
            // data-turbo-permanent is set from JavaScript while the viewer is busy, never sent by the server, except
            // on the per-user subscription, which must survive every visit
            ->assertElementCount('[data-turbo-permanent]', 1)
            // Back requests the page again, from Turbo's cache and from the browser's, so it shows what is current
            ->assertElementCount('head meta[name="turbo-cache-control"][content="no-cache"]', 1)
            ->assertHeaderContains('Cache-Control', 'no-store')
            // Links prefetch on hover as on any other page: the response that comes back sets the same cookie
            ->assertElementCount('head meta[name="turbo-prefetch"]', 0);

        // The cookie grants the viewer's own topic only, as on every page, so no later response can narrow it
        self::assertSame(['subscribe' => ['user/' . $user->getPublicId()]], MercureCookie::grantedTopics($browser));
    }

    public function testTogglingTheLockRefreshesTheOrderPage(): void
    {
        $order = CustomerOrderFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/order/' . $order->getPublicId()->value())
            ->interceptRedirects()
            ->click('button[data-order-lock]')
            ->assertRedirected();

        $this->assertOneRefreshOf($order);
    }

    public function testAllocatingRefreshesTheOrderPageOnce(): void
    {
        $order = $this->allocatableOrder(itemCount: 3);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/order/' . $order->getPublicId()->value())
            ->interceptRedirects()
            ->click('Allocate')
            ->assertRedirected();

        // Purchase orders, their items and every status change, in one flush: one refresh
        self::assertCount(1, QueuedNotifications::handle(self::getContainer()));
        $this->assertRefreshedOnce($order);
        self::assertCount(1, $order->getPurchaseOrders());
    }

    public function testAddingAnItemRefreshesTheOrderPage(): void
    {
        $order = CustomerOrderFactory::createOne();
        $product = ProductFactory::new()->withActiveSource()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/order/' . $order->getPublicId()->value() . '/item/new')
            ->fillField('order_item[productId]', (string) $product->getId())
            ->fillField('order_item[quantity]', '5')
            ->interceptRedirects()
            ->click('Create Order Item')
            ->assertRedirected();

        $this->assertOneRefreshOf($order);
    }

    public function testEditingAnItemRefreshesTheOrderPage(): void
    {
        $item = CustomerOrderItemFactory::createOne(['product' => ProductFactory::new()->withActiveSource()->create(), 'quantity' => 5]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/order/item/' . $item->getPublicId()->value() . '/edit')
            ->fillField('update_order_item[quantity]', '10')
            ->fillField('update_order_item[priceIncVat]', '30.00')
            ->interceptRedirects()
            ->click('Update Order Item')
            ->assertRedirected();

        $this->assertOneRefreshOf($item->getCustomerOrder());
    }

    public function testRemovingAnItemRefreshesTheOrderPage(): void
    {
        $item = CustomerOrderItemFactory::createOne(['product' => ProductFactory::new()->withActiveSource()->create(), 'quantity' => 5]);
        $order = $item->getCustomerOrder();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/order/item/' . $item->getPublicId()->value() . '/edit')
            ->fillField('update_order_item[quantity]', '0')
            ->interceptRedirects()
            ->click('Update Order Item')
            ->assertRedirected();

        $this->assertOneRefreshOf($order);
    }

    public function testCancellingAnItemRefreshesTheOrderPage(): void
    {
        $item = CustomerOrderItemFactory::createOne(['customerOrder' => CustomerOrderFactory::createOne()]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/order/item/' . $item->getPublicId()->value() . '/edit')
            ->interceptRedirects()
            ->click('Cancel Order Item')
            ->assertRedirected();

        $this->assertOneRefreshOf($item->getCustomerOrder());
    }

    public function testRejectingAPurchaseOrderItemRefreshesItsOrderPage(): void
    {
        $poItem = $this->purchaseOrderItem();
        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        self::getContainer()->get(EntityManagerInterface::class)->flush();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/purchase/order/item/' . $poItem->getPublicId()->value() . '/edit/status')
            ->fillField('purchase_order_item_status[purchaseOrderItemStatus]', PurchaseOrderStatus::REJECTED->value)
            ->interceptRedirects()
            ->click('Update')
            ->assertRedirected();

        $this->assertOneRefreshOf($poItem->getPurchaseOrder()->getCustomerOrder());
    }

    public function testEditingAPurchaseOrderItemsQuantityRefreshesItsOrderPage(): void
    {
        // 5 of the order item's 10 stay outstanding, so its status does not change and raises nothing
        $poItem = $this->purchaseOrderItem();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/purchase/order/item/' . $poItem->getPublicId()->value() . '/edit')
            ->fillField('purchase_order_item_quantity[quantity]', '5')
            ->interceptRedirects()
            ->click('Update')
            ->assertRedirected();

        $this->assertOneRefreshOf($poItem->getPurchaseOrder()->getCustomerOrder());
    }

    public function testRemovingAPurchaseOrderItemRefreshesItsOrderPage(): void
    {
        $poItem = $this->purchaseOrderItem();
        $order = $poItem->getPurchaseOrder()->getCustomerOrder();
        // The order item keeps a purchase order item on the same purchase order, so neither changes status
        PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $poItem->getCustomerOrderItem(),
            'supplierProduct' => $poItem->getSupplierProduct(),
            'purchaseOrder' => $poItem->getPurchaseOrder(),
            'quantity' => 2,
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/purchase/order/item/' . $poItem->getPublicId()->value() . '/edit')
            ->fillField('purchase_order_item_quantity[quantity]', '0')
            ->interceptRedirects()
            ->click('Update')
            ->assertRedirected();

        $this->assertOneRefreshOf($order);
    }

    public function testRemovingAPurchaseOrdersLastItemRefreshesItsOrderPage(): void
    {
        $poItem = $this->purchaseOrderItem();
        $order = $poItem->getPurchaseOrder()->getCustomerOrder();
        $purchaseOrderId = $poItem->getPurchaseOrder()->getId();
        // On another purchase order, so the order item's status does not change
        PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => $poItem->getCustomerOrderItem(),
            'supplierProduct' => $poItem->getSupplierProduct(),
            'customerOrder' => $order,
            'quantity' => 2,
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/purchase/order/item/' . $poItem->getPublicId()->value() . '/edit')
            ->fillField('purchase_order_item_quantity[quantity]', '0')
            ->interceptRedirects()
            ->click('Update')
            ->assertRedirected();

        self::assertNull(PurchaseOrderFactory::repository()->find($purchaseOrderId));
        $this->assertOneRefreshOf($order);
    }

    public function testHubFailureKeepsTheChangeAndDoesNotFailTheMessage(): void
    {
        $order = CustomerOrderFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/order/' . $order->getPublicId()->value())
            ->interceptRedirects()
            ->click('button[data-order-lock]')
            ->assertRedirected();
        QueuedNotifications::hub(self::getContainer())->failWith(new RuntimeException('Hub is down'));

        // Throws HandlerFailedException if the handler let the failure through
        $handled = QueuedNotifications::handle(self::getContainer());

        self::assertCount(1, $handled);
        self::assertInstanceOf(RefreshOrderPage::class, $handled[0]);
        self::assertSame([], QueuedNotifications::published(self::getContainer()));
        self::assertNotNull(CustomerOrderFactory::repository()->find($order->getId())?->getOrderLock());
    }

    private function assertOneRefreshOf(CustomerOrder $order): void
    {
        $handled = QueuedNotifications::handle(self::getContainer());

        self::assertCount(1, $handled);
        self::assertInstanceOf(RefreshOrderPage::class, $handled[0]);
        $this->assertRefreshedOnce($order);
    }

    /** Public, on the topic the order page subscribes to: it carries no data, and each viewer's reload checks access. */
    private function assertRefreshedOnce(CustomerOrder $order): void
    {
        $updates = QueuedNotifications::published(self::getContainer());

        self::assertCount(1, $updates);
        self::assertInstanceOf(Update::class, $updates[0]);
        self::assertSame(['order/' . $order->getPublicId()->value()], $updates[0]->getTopics());
        self::assertFalse($updates[0]->isPrivate());
        self::assertSame(MercureLiveUpdatePublisher::REFRESH_STREAM, $updates[0]->getData());
    }

    private function allocatableOrder(int $itemCount): CustomerOrder
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();

        for ($i = 0; $i < $itemCount; ++$i) {
            $product = ProductFactory::createOne(['isActive' => true]);
            SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => $product, 'stock' => 100]);
            CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'product' => $product, 'quantity' => 5]);
        }

        return $order;
    }

    private function purchaseOrderItem(): PurchaseOrderItem
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $supplier = SupplierFactory::createOne(['isActive' => true]);
        $order = CustomerOrderFactory::createOne();

        return PurchaseOrderItemFactory::createOne([
            'customerOrderItem' => CustomerOrderItemFactory::createOne(['customerOrder' => $order, 'product' => $product, 'quantity' => 10]),
            'supplierProduct' => SupplierProductFactory::createOne(['supplier' => $supplier, 'product' => $product, 'stock' => 100]),
            'supplier' => $supplier,
            'product' => $product,
            'customerOrder' => $order,
            'quantity' => 3,
        ]);
    }
}
