<?php

declare(strict_types=1);

namespace App\Tests\Customer\Infrastructure\Persistence\Doctrine;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\ShippingMethod;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class UserDoctrineRepositoryInsightsTest extends KernelTestCase
{
    private UserRepository $users;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->users = self::getContainer()->get(UserRepository::class);
    }

    public function testCustomerWithNoOrdersIsNew(): void
    {
        $customer = UserFactory::createOne();

        $insights = $this->users->findCustomerInsights($customer);

        self::assertSame(0, (int) $insights['orderCount']);
        self::assertSame('new', $insights['segment']);
    }

    public function testCountsTheCustomersOrders(): void
    {
        $customer = UserFactory::createOne();
        CustomerOrderFactory::createOne(['customer' => $customer]);
        CustomerOrderFactory::createOne(['customer' => $customer]);
        CustomerOrderFactory::createOne();

        $insights = $this->users->findCustomerInsights($customer);

        self::assertSame(2, (int) $insights['orderCount']);
        self::assertSame('returning', $insights['segment']);
    }

    public function testRevenueAndAverageOrderValueAreExVatLikeTheReports(): void
    {
        $customer = UserFactory::createOne();
        $first = $this->createOrder($customer, '100.00', '120.00');
        $second = $this->createOrder($customer, '50.00', '60.00');
        self::assertNotSame($first->getTotalPrice(), $first->getTotalPriceIncVat());

        $insights = $this->users->findCustomerInsights($customer);

        $revenue = (float) $first->getTotalPrice() + (float) $second->getTotalPrice();
        self::assertEqualsWithDelta($revenue, (float) $insights['totalRevenue'], 0.001);
        self::assertEqualsWithDelta($revenue / 2, (float) $insights['averageOrderValue'], 0.001);
    }

    public function testRankFollowsExVatRevenue(): void
    {
        // Ahead ex VAT, behind inc VAT
        $lowVat = UserFactory::createOne();
        $lowVatOrder = $this->createOrder($lowVat, '100.00', '105.00');
        $highVat = UserFactory::createOne();
        $highVatOrder = $this->createOrder($highVat, '90.00', '108.00');
        self::assertGreaterThan((float) $highVatOrder->getTotalPrice(), (float) $lowVatOrder->getTotalPrice());
        self::assertLessThan((float) $highVatOrder->getTotalPriceIncVat(), (float) $lowVatOrder->getTotalPriceIncVat());

        self::assertSame(1, (int) $this->users->findCustomerInsights($lowVat)['revenueRank']);
        self::assertSame(2, (int) $this->users->findCustomerInsights($highVat)['revenueRank']);
    }

    /**
     * @param numeric-string $price
     * @param numeric-string $priceIncVat
     */
    private function createOrder(User $customer, string $price, string $priceIncVat): CustomerOrder
    {
        $order = CustomerOrderFactory::createOne(['customer' => $customer, 'shippingMethod' => ShippingMethod::THREE_DAY]);
        $item = CustomerOrderItemFactory::createOne(['customerOrder' => $order]);
        $item->updateItem(1, $price, $priceIncVat, 1000);

        $order->recalculateTotal();
        self::getContainer()->get('doctrine.orm.entity_manager')->flush();

        return $order;
    }
}
