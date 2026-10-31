<?php

declare(strict_types=1);

namespace App\Tests\Audit\UI;

use App\Shared\Domain\Event\DomainEventType;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\PurchaseOrderItemFactory;
use App\Tests\Shared\Factory\StatusChangeLogFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Story\StaffUserStory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Attribute\WithStory;

// Creating a purchase order item re-derives the order item's status, which the audit logs against the current user
#[WithStory(StaffUserStory::class)]
final class StatusLogControllerTest extends WebTestCase
{
    use HasBrowser;

    /**
     * @return iterable<string, array{string, DomainEventType, DomainEventType, string}>
     */
    public static function statusLogs(): iterable
    {
        yield 'order' => ['/status/log/order/', DomainEventType::ORDER_STATUS_CHANGED, DomainEventType::ORDER_ITEM_STATUS_CHANGED, 'Customer Order Created'];
        yield 'order item' => ['/status/log/order/item/', DomainEventType::ORDER_ITEM_STATUS_CHANGED, DomainEventType::ORDER_STATUS_CHANGED, 'Customer Order Item Created'];
        yield 'purchase order' => ['/status/log/purchase/order/', DomainEventType::PURCHASE_ORDER_STATUS_CHANGED, DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED, 'Purchase Order Created'];
        yield 'purchase order item' => ['/status/log/purchase/order/item/', DomainEventType::PURCHASE_ORDER_ITEM_STATUS_CHANGED, DomainEventType::PURCHASE_ORDER_STATUS_CHANGED, 'Purchase Order Item Created'];
    }

    #[DataProvider('statusLogs')]
    public function testShowsTheRecordsOwnStatusChanges(
        string $path,
        DomainEventType $eventType,
        DomainEventType $otherEventType,
        string $created,
    ): void {
        $record = match ($eventType) {
            DomainEventType::ORDER_STATUS_CHANGED => CustomerOrderFactory::createOne(),
            DomainEventType::ORDER_ITEM_STATUS_CHANGED => CustomerOrderItemFactory::createOne(),
            DomainEventType::PURCHASE_ORDER_STATUS_CHANGED => PurchaseOrderFactory::createOne(),
            default => PurchaseOrderItemFactory::createOne(),
        };
        $id = $record->getId();
        \assert($id !== null);

        StatusChangeLogFactory::createOne(['eventType' => $eventType, 'eventTypeId' => $id, 'status' => 'LOGGED']);
        // The same id under another event type belongs to a different record
        StatusChangeLogFactory::createOne(['eventType' => $otherEventType, 'eventTypeId' => $id, 'status' => 'UNRELATED']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($path . $record->getPublicId()->value())
            ->assertSuccessful()
            ->assertSee($created)
            ->assertSee('Logged')
            ->assertNotSee('Unrelated');
    }

    public function testUnknownRecordIsNotFound(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/status/log/order/01ARZ3NDEKTSV4RRFFQ69G5FAV')
            ->assertStatus(404);
    }
}
