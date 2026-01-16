<?php

namespace App\Tests\Unit\Service\Order;

use App\Order\Application\DTO\EditOrderItemDto;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderItemDoctrineRepository;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\EditOrderItem;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class EditOrderItemTest extends TestCase
{
    private MockObject $em;

    private MockObject $markupCalculator;

    private EditOrderItem $editOrderItem;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->markupCalculator = $this->createMock(MarkupCalculator::class);
        $this->editOrderItem = new EditOrderItem($this->em, $this->markupCalculator);
    }

    public function testHandleWithNonEditOrderItemDtoEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be instance of EditOrderItemDto');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->editOrderItem)($context);
    }

    public function testEditOrderItemSuccessfully(): void
    {
        $dto = $this->createMock(EditOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(2);
        $dto->method('getPriceIncVat')->willReturn('100.00');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrder = $this->createMock(CustomerOrder::class);

        $this->em->method('getRepository')->willReturnMap([
            [CustomerOrderItem::class, $this->createMock(CustomerOrderItemDoctrineRepository::class)],
        ]);

        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20.00');

        $this->em->getRepository(CustomerOrderItem::class)->method('find')->willReturn($customerOrderItem);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);
        $customerOrderItem->method('getVatRate')->willReturn($vatRate);

        $this->markupCalculator->method('calculateSellPriceBeforeVat')->willReturn('83.33');

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        ($this->editOrderItem)($context);
    }

    public function testEditOrderItemWithZeroQuantity(): void
    {
        $dto = $this->createMock(EditOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(0);
        $dto->method('getPriceIncVat')->willReturn('100.00');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrder = $this->createMock(CustomerOrder::class);

        $this->em->method('getRepository')->willReturnMap([
            [CustomerOrderItem::class, $this->createMock(CustomerOrderItemDoctrineRepository::class)],
        ]);

        $this->em->getRepository(CustomerOrderItem::class)->method('find')->willReturn($customerOrderItem);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);

        $this->em->expects($this->once())->method('remove');
        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        ($this->editOrderItem)($context);
    }

    public function testEditOrderItemWithQuantityLessThanPoQuantity(): void
    {
        $dto = $this->createMock(EditOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(1);
        $dto->method('getPriceIncVat')->willReturn('100.00');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $customerOrderItem = $this->createMock(CustomerOrderItem::class);
        $customerOrder = $this->createMock(CustomerOrder::class);

        $this->em->method('getRepository')->willReturnMap([
            [CustomerOrderItem::class, $this->createMock(CustomerOrderItemDoctrineRepository::class)],
        ]);

        $this->em->getRepository(CustomerOrderItem::class)->method('find')->willReturn($customerOrderItem);
        $customerOrderItem->method('getCustomerOrder')->willReturn($customerOrder);
        $customerOrderItem->method('getQtyAddedToPurchaseOrders')->willReturn(2);

        $this->em->expects($this->never())->method('flush');

        ($this->editOrderItem)($context);
    }
}
