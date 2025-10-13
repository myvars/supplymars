<?php

namespace App\Tests\Integration\Service\Order;

use App\Order\Application\DTO\CreateOrderItemDto;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\CreateOrderItem;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\CustomerOrderItemFactory;
use tests\Shared\Factory\ProductFactory;
use Story\StaffUserStory;
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
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->createOrderItem = new CreateOrderItem($em, $validator);
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

        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->createOrderItem)($context);

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
