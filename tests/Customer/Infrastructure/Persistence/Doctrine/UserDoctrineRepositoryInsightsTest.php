<?php

declare(strict_types=1);

namespace App\Tests\Customer\Infrastructure\Persistence\Doctrine;

use App\Customer\Domain\Repository\UserRepository;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UserDoctrineRepositoryInsightsTest extends KernelTestCase
{
    use Factories;

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
}
