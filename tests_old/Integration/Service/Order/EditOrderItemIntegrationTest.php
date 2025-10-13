<?php

namespace App\Tests\Integration\Service\Order;

use App\Order\Application\DTO\EditOrderItemDto;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\EditOrderItem;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\CustomerOrderItemFactory;
use tests\Shared\Factory\ProductFactory;
use tests\Shared\Factory\PurchaseOrderItemFactory;
use Story\StaffUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class EditOrderItemIntegrationTest extends KernelTestCase
{
    use Factories;

    private EditOrderItem $editOrderItem;

    protected function setUp(): void
    {
        self::bootKernel();
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $markupCalculator = static::getContainer()->get(MarkupCalculator::class);
        $this->editOrderItem = new EditOrderItem($em, $markupCalculator);
        StaffUserStory::load();
    }

    public function testHandleWithValidEditOrderItemDto(): void
    {
        $product = ProductFactory::createOne(['sellPriceIncVat' => '50.00']);
        $customerOrderItem = CustomerOrderItemFactory::createOne(['product' => $product]);

        $dto = new EditOrderItemDto(
            $customerOrderItem->getId(),
            2,
            '100.00'
        );

        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->editOrderItem)($context);

        $updatedCustomerOrderItem = CustomerOrderItemFactory::repository()->find($customerOrderItem->getId());

        $this->assertInstanceOf(CustomerOrderItem::class, $updatedCustomerOrderItem);
        $this->assertSame(2, $updatedCustomerOrderItem->getQuantity());
        $this->assertSame('100.00', $updatedCustomerOrderItem->getPriceIncVat());
    }

    public function testHandleWithNewZeroQuantity(): void
    {
        $product = ProductFactory::createOne(['sellPriceIncVat' => '50.00']);
        $customerOrderItem = CustomerOrderItemFactory::createOne(['product' => $product]);
        $customerOrderItemId = $customerOrderItem->getId();

        $dto = new EditOrderItemDto(
            $customerOrderItem->getId(),
            0,
            '50.00'
        );

        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->editOrderItem)($context);

        $this->assertNull(CustomerOrderItemFactory::repository()->find($customerOrderItemId));
    }

    public function testHandleWithNewQuantityLessThanPoQuantity(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $customerOrderItemId = $purchaseOrderItem->getCustomerOrderItem()->getId();
        $customerOrderItemQty = $purchaseOrderItem->getCustomerOrderItem()->getQuantity();

        $dto = new EditOrderItemDto(
            $customerOrderItemId,
            0,
            '50.00'
        );

        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->editOrderItem)($context);

        $customerOrderItem = CustomerOrderItemFactory::repository()->find($customerOrderItemId);

        $this->assertSame($customerOrderItemQty, $customerOrderItem->getQuantity());
    }
}
