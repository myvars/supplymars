<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Order\Application\Message\RefreshOrderPage;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderItem;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Shared\Domain\Event\DomainEventType;
use App\Shared\Infrastructure\Notification\MercureLiveUpdatePublisher;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\StatusChangeLogFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Mercure\MercureCookie;
use App\Tests\Shared\Mercure\QueuedNotifications;
use App\Tests\Shared\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mercure\Update;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

/**
 * A purchase order's page refreshes with its order's page: it subscribes to the order's topic, which every change
 * to the order, its purchase orders and their items refreshes (RefreshOrderPageForViewers).
 */
#[WithStory(StaffUserStory::class)]
final class PurchaseOrderLiveUpdatesFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testPurchaseOrderPageSubscribesToItsOrdersTopic(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $purchaseOrder = $this->purchaseOrderItem()->getPurchaseOrder();
        $topic = 'order/' . $purchaseOrder->getCustomerOrder()->getPublicId()->value();

        $browser = $this->browser()
            ->actingAs($user)
            ->visit('/purchase/order/' . $purchaseOrder->getPublicId()->value())
            ->assertSuccessful()
            ->assertElementCount('turbo-frame#body turbo-mercure-stream-source[private][src$="?match=' . rawurlencode($topic) . '"]', 1)
            ->assertElementCount('head meta[name="turbo-cache-control"][content="no-cache"]', 1)
            ->assertHeaderContains('Cache-Control', 'no-store')
            ->assertElementCount('head meta[name="turbo-prefetch"]', 0);

        self::assertSame(['subscribe' => ['user/' . $user->getPublicId()]], MercureCookie::grantedTopics($browser));
    }

    public function testChangingAnItemsStatusRefreshesThePurchaseOrderPage(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $poItem = $this->purchaseOrderItem();
        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        // The setup's own refresh: handled now, so the form's is not dropped as its duplicate
        QueuedNotifications::handle(self::getContainer());

        $browser = $this->browser()
            ->actingAs($user)
            ->visit('/purchase/order/' . $poItem->getPurchaseOrder()->getPublicId()->value());
        $pageTopic = $this->pageTopic($browser);

        $browser
            ->visit('/purchase/order/item/' . $poItem->getPublicId()->value() . '/edit/status')
            ->fillField('purchase_order_item_status[purchaseOrderItemStatus]', PurchaseOrderStatus::REJECTED->value)
            ->interceptRedirects()
            ->click('Update')
            ->assertRedirected();

        $handled = QueuedNotifications::handle(self::getContainer());
        self::assertCount(1, $handled);
        self::assertInstanceOf(RefreshOrderPage::class, $handled[0]);

        $updates = QueuedNotifications::published(self::getContainer());
        self::assertCount(1, $updates);
        self::assertInstanceOf(Update::class, $updates[0]);
        self::assertSame(MercureLiveUpdatePublisher::REFRESH_STREAM, $updates[0]->getData());
        self::assertSame([$pageTopic], $updates[0]->getTopics());
    }

    public function testRewindingRefreshesThePurchaseOrderPage(): void
    {
        $poItem = $this->purchaseOrderItem();
        $poItem->updateItemStatus(PurchaseOrderStatus::PROCESSING);
        self::getContainer()->get(EntityManagerInterface::class)->flush();
        QueuedNotifications::handle(self::getContainer());
        $purchaseOrderId = $poItem->getPurchaseOrder()->getPublicId()->value();

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->visit('/purchase/order/' . $purchaseOrderId);
        $pageTopic = $this->pageTopic($browser);

        // Processing and pending both leave the order item processing, so only the rewind raises an event
        $browser
            ->visit('/purchase/order/' . $purchaseOrderId . '/rewind/confirm')
            ->interceptRedirects()
            ->click('Rewind to Pending')
            ->assertRedirected();

        $handled = QueuedNotifications::handle(self::getContainer());
        self::assertCount(1, $handled);
        self::assertInstanceOf(RefreshOrderPage::class, $handled[0]);

        $updates = QueuedNotifications::published(self::getContainer());
        self::assertCount(1, $updates);
        self::assertInstanceOf(Update::class, $updates[0]);
        self::assertSame([$pageTopic], $updates[0]->getTopics());

        // Forced, not a status change: the rewind leaves no purchase order status log behind
        foreach ([DomainEventType::PURCHASE_ORDER_STATUS_CHANGED, DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED] as $type) {
            self::assertSame(0, StatusChangeLogFactory::count(['eventType' => $type]));
        }
    }

    /** The topic the page's own stream source subscribes to, so a refresh is checked against what the page listens on. */
    private function pageTopic(KernelBrowser $browser): string
    {
        $src = $browser->crawler()->filter('turbo-frame#body turbo-mercure-stream-source')->attr('src');
        self::assertIsString($src);
        parse_str((string) parse_url($src, \PHP_URL_QUERY), $query);
        self::assertIsString($query['match'] ?? null);

        return $query['match'];
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
