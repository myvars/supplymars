<?php

declare(strict_types=1);

namespace App\Tests\Order\UI;

use App\Order\Application\Handler\CreateDemoOrderHandler;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class DemoOrderFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testConfirmPageShowsCreateButton(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/demo/confirm')
            ->assertSuccessful()
            ->assertSee('Create Demo Order')
            ->assertSee('0 of ' . CreateDemoOrderHandler::DAILY_LIMIT . ' demo orders created today');
    }

    public function testConfirmPageShowsLimitReachedWhenAtLimit(): void
    {
        $this->createDemoOrders(CreateDemoOrderHandler::DAILY_LIMIT);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/demo/confirm')
            ->assertSuccessful()
            ->assertSee('You have reached the daily limit');
    }

    public function testSuccessfulDemoOrderCreation(): void
    {
        $this->createTestProducts(3);
        $initialCount = CustomerOrderFactory::count();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/demo/confirm')
            ->click('Create Order')
            ->assertSuccessful()
            ->assertNotOn('/order/demo')
            ->assertSee('Demo order #');

        CustomerOrderFactory::assert()->count($initialCount + 1);
    }

    public function testDemoOrderCreationBlockedAtLimit(): void
    {
        $this->createTestProducts(3);
        $this->createDemoOrders(CreateDemoOrderHandler::DAILY_LIMIT);

        $initialCount = CustomerOrderFactory::count();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->interceptRedirects()
            ->get('/order/demo/confirm')
            ->assertSuccessful()
            ->assertSee('You have reached the daily limit');

        CustomerOrderFactory::assert()->count($initialCount);
    }

    public function testDemoOrderButtonVisibleOnIndex(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/')
            ->assertSuccessful()
            ->assertSee('Create Demo Order');
    }

    private function createTestProducts(int $count): void
    {
        $supplier = SupplierFactory::createOne(['isActive' => true]);

        for ($i = 0; $i < $count; ++$i) {
            $product = ProductFactory::createOne(['isActive' => true]);
            SupplierProductFactory::createOne([
                'supplier' => $supplier,
                'product' => $product,
                'stock' => 100,
            ]);
        }

        UserFactory::createOne();
    }

    private function createDemoOrders(int $count): void
    {
        for ($i = 0; $i < $count; ++$i) {
            CustomerOrderFactory::createOne([
                'customerOrderRef' => 'DEMO-' . sprintf('%04d', $i),
            ]);
        }
    }
}
