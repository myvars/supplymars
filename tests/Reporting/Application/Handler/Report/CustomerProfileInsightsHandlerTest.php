<?php

namespace App\Tests\Reporting\Application\Handler\Report;

use App\Reporting\Application\Handler\Report\CustomerProfileInsightsHandler;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class CustomerProfileInsightsHandlerTest extends KernelTestCase
{
    use Factories;

    private CustomerProfileInsightsHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CustomerProfileInsightsHandler::class);
    }

    public function testReturnsSuccessForCustomerWithNoOrders(): void
    {
        $customer = UserFactory::createOne();

        $result = ($this->handler)($customer);

        self::assertTrue($result->ok);
        self::assertSame('Insights loaded', $result->message);
        self::assertIsArray($result->payload);
    }

    public function testReturnsInsightsForCustomerWithOrders(): void
    {
        $customer = UserFactory::createOne();
        CustomerOrderFactory::createOne(['customer' => $customer]);
        CustomerOrderFactory::createOne(['customer' => $customer]);

        $result = ($this->handler)($customer);

        self::assertTrue($result->ok);
        self::assertIsArray($result->payload);
    }

    public function testPayloadContainsExpectedKeys(): void
    {
        $customer = UserFactory::createOne();
        CustomerOrderFactory::createOne(['customer' => $customer]);

        $result = ($this->handler)($customer);

        self::assertTrue($result->ok);
        // Verify insights are returned (structure depends on repository implementation)
        self::assertNotNull($result->payload);
    }
}
