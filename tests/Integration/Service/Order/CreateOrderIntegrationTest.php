<?php

namespace App\Tests\Integration\Service\Order;

use App\DTO\CreateOrderDto;
use App\Entity\CustomerOrder;
use App\Enum\ShippingMethod;
use App\Factory\AddressFactory;
use App\Factory\CustomerOrderFactory;
use App\Factory\UserFactory;
use App\Factory\VatRateFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\CreateOrder;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CreateOrderIntegrationTest extends KernelTestCase
{
    use Factories;

    private CreateOrder $createOrder;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->createOrder = new CreateOrder($entityManager, $validator);
        StaffUserStory::load();
    }

    public function testHandleWithValidCreateOrderDto(): void
    {
        $customer = UserFactory::createOne();
        $address = AddressFactory::createOne([
            'customer' => $customer,
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true
        ])->_real();
        $customer->addAddress($address);

        VatRateFactory::new()->standard()->create();

        $dto = new CreateOrderDto();
        $dto->setCustomerId($customer->getId());
        $dto->setShippingMethod(ShippingMethod::NEXT_DAY);
        $dto->setCustomerOrderRef('order_ref');

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->createOrder->handle($crudOptions);

        $customerOrder = CustomerOrderFactory::repository()->findOneBy([
            'customer' => $customer,
            'customerOrderRef' => 'order_ref'
        ])->_real();

        $this->assertInstanceOf(CustomerOrder::class, $customerOrder);
        $this->assertSame('order_ref', $customerOrder->getCustomerOrderRef());
    }
}
