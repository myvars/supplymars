<?php

namespace App\Tests\Integration\Service\Order;

use App\Order\Application\DTO\CreateOrderDto;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\CreateOrder;
use App\Shared\Domain\ValueObject\ShippingMethod;
use Doctrine\ORM\EntityManagerInterface;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use tests\Shared\Factory\AddressFactory;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\UserFactory;
use tests\Shared\Factory\VatRateFactory;
use Zenstruck\Foundry\Test\Factories;

class CreateOrderIntegrationTest extends KernelTestCase
{
    use Factories;

    private CreateOrder $createOrder;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->createOrder = new CreateOrder($em, $validator);
        StaffUserStory::load();
    }

    public function testHandleWithValidCreateOrderDto(): void
    {
        $customer = UserFactory::createOne();
        $address = AddressFactory::createOne([
            'customer' => $customer,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
        ]);
        $customer->addAddress($address);

        VatRateFactory::new()->withStandardRate()->create();

        $dto = new CreateOrderDto();
        $dto->setCustomerId($customer->getId());
        $dto->setShippingMethod(ShippingMethod::NEXT_DAY);
        $dto->setCustomerOrderRef('order_ref');

        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->createOrder)($context);

        $customerOrder = CustomerOrderFactory::repository()->findOneBy([
            'customer' => $customer,
            'customerOrderRef' => 'order_ref',
        ]);

        $this->assertInstanceOf(CustomerOrder::class, $customerOrder);
        $this->assertSame('order_ref', $customerOrder->getCustomerOrderRef());
    }
}
