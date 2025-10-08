<?php

namespace App\Tests\Integration\Service\Order;

use App\DTO\EditOrderItemDto;
use App\Entity\CustomerOrderItem;
use App\Factory\CustomerOrderItemFactory;
use App\Factory\ProductFactory;
use App\Factory\PurchaseOrderItemFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\EditOrderItem;
use App\Service\Product\MarkupCalculator;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class EditOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private EditOrderItem $editOrderItem;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $markupCalculator = static::getContainer()->get(MarkupCalculator::class);
        $this->editOrderItem = new EditOrderItem($entityManager, $markupCalculator);
        StaffUserStory::load();
    }

    public function testHandleWithValidEditOrderItemDto(): void
    {
        $product = ProductFactory::createOne(['sellPriceIncVat' => '50.00'])->_real();
        $customerOrderItem = CustomerOrderItemFactory::createOne(['product' => $product])->_real();

        $dto = new EditOrderItemDto(
            $customerOrderItem->getId(),
            2,
            '100.00'
        );

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->editOrderItem->handle($crudOptions);

        $updatedCustomerOrderItem = CustomerOrderItemFactory::repository()->find($customerOrderItem->getId())->_real();

        $this->assertInstanceOf(CustomerOrderItem::class, $updatedCustomerOrderItem);
        $this->assertSame(2, $updatedCustomerOrderItem->getQuantity());
        $this->assertSame('100.00', $updatedCustomerOrderItem->getPriceIncVat());
    }

    public function testHandleWithNewZeroQuantity(): void
    {
        $product = ProductFactory::createOne(['sellPriceIncVat' => '50.00'])->_real();
        $customerOrderItem = CustomerOrderItemFactory::createOne(['product' => $product])->_real();
        $customerOrderItemId = $customerOrderItem->getId();

        $dto = new EditOrderItemDto(
            $customerOrderItem->getId(),
            0,
            '50.00'
        );

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->editOrderItem->handle($crudOptions);

        $this->assertNull(CustomerOrderItemFactory::repository()->find($customerOrderItemId));
    }

    public function testHandleWithNewQuantityLessThanPoQuantity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne()->_real();
        $customerOrderItemId = $purchaseOrderItem->getCustomerOrderItem()->getId();
        $customerOrderItemQty = $purchaseOrderItem->getCustomerOrderItem()->getQuantity();

        $dto = new EditOrderItemDto(
            $customerOrderItemId,
            0,
            '50.00'
        );

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->editOrderItem->handle($crudOptions);

        $customerOrderItem = CustomerOrderItemFactory::repository()->find($customerOrderItemId);

        $this->assertSame($customerOrderItemQty, $customerOrderItem->getQuantity());
    }
}
