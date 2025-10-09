<?php

namespace App\Tests\Integration\Service\Order;

use App\DTO\CreateOrderItemDto;
use App\Entity\CustomerOrderItem;
use App\Factory\CustomerOrderFactory;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\ProductFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\CreateOrderItem;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CreateOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private CreateOrderItem $createOrderItem;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->createOrderItem = new CreateOrderItem($entityManager, $validator);
        StaffUserStory::load();
    }

    public function testHandleWithValidCreateOrderItemDto(): void
    {
        $customerOrder = CustomerOrderFactory::createOne();
        $product = ProductFactory::createOne();

        $dto = new CreateOrderItemDto(
            $customerOrder->getId(),
            $product->getId(),
            2
        );

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->createOrderItem->handle($crudOptions);

        $customerOrderItem = CustomerOrderItemFactory::repository()->findOneBy([
            'customerOrder' => $customerOrder,
            'product' => $product
        ]);

        $this->assertInstanceOf(CustomerOrderItem::class, $customerOrderItem);
        $this->assertSame($customerOrder, $customerOrderItem->getCustomerOrder());
        $this->assertSame($product, $customerOrderItem->getProduct());
        $this->assertSame(2, $customerOrderItem->getQuantity());
    }
}
