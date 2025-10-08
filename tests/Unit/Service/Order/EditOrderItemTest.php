<?php

namespace App\Tests\Unit\Service\Order;

use PHPUnit\Framework\MockObject\MockObject;
use App\DTO\EditOrderItemDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\VatRate;
use App\Repository\CustomerOrderItemRepository;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\EditOrderItem;
use App\Service\Product\MarkupCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class EditOrderItemTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $markupCalculator;

    private EditOrderItem $editOrderItem;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->markupCalculator = $this->createMock(MarkupCalculator::class);
        $this->editOrderItem = new EditOrderItem($this->entityManager, $this->markupCalculator);
    }

    public function testHandleWithNonEditOrderItemDtoEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be instance of EditOrderItemDto');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->editOrderItem->handle($crudOptions);
    }

    public function testEditOrderItemSuccessfully(): void
    {
        $dto = $this->createMock(EditOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(2);
        $dto->method('getPriceIncVat')->willReturn('100.00');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrder = $this->createMock(CustomerOrder::class);

        $this->entityManager->method('getRepository')->willReturnMap([
            [CustomerOrderItem::class, $this->createMock(CustomerOrderItemRepository::class)]
        ]);

        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20.00');

        $this->entityManager->getRepository(CustomerOrderItem::class)->method('find')->willReturn($customerOrderItem);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);
        $customerOrderItem->method('getVatRate')->willReturn($vatRate);

        $this->markupCalculator->method('calculateSellPriceBeforeVat')->willReturn('83.33');

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->editOrderItem->handle($crudOptions);
    }

    public function testEditOrderItemWithZeroQuantity(): void
    {
        $dto = $this->createMock(EditOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(0);
        $dto->method('getPriceIncVat')->willReturn('100.00');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrder = $this->createMock(CustomerOrder::class);

        $this->entityManager->method('getRepository')->willReturnMap([
            [CustomerOrderItem::class, $this->createMock(CustomerOrderItemRepository::class)]
        ]);

        $this->entityManager->getRepository(CustomerOrderItem::class)->method('find')->willReturn($customerOrderItem);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);

        $this->entityManager->expects($this->once())->method('remove');
        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $this->editOrderItem->handle($crudOptions);
    }

    public function testEditOrderItemWithQuantityLessThanPoQuantity(): void
    {
        $dto = $this->createMock(EditOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(1);
        $dto->method('getPriceIncVat')->willReturn('100.00');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrder = $this->createMock(CustomerOrder::class);

        $this->entityManager->method('getRepository')->willReturnMap([
            [CustomerOrderItem::class, $this->createMock(CustomerOrderItemRepository::class)]
        ]);

        $this->entityManager->getRepository(CustomerOrderItem::class)->method('find')->willReturn($customerOrderItem);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);
        $customerOrderItem->method('getQtyAddedToPurchaseOrders')->willReturn(2);

        $this->entityManager->expects($this->never())->method('flush');

        $this->editOrderItem->handle($crudOptions);
    }
}
