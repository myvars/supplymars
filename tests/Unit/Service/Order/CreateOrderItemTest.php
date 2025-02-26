<?php

namespace App\Tests\Unit\Service\Order;

use App\DTO\CreateOrderItemDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use App\Repository\CustomerOrderRepository;
use App\Repository\ProductRepository;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\CreateOrderItem;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateOrderItemTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private DomainEventDispatcher $domainEventDispatcher;
    private CreateOrderItem $createOrderItem;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->domainEventDispatcher = $this->createMock(DomainEventDispatcher::class);
        $this->createOrderItem = new CreateOrderItem($this->entityManager, $this->validator, $this->domainEventDispatcher);
    }

    public function testHandleWithNonCreateOrderItemDtoEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of CreateOrderItemDto');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->createOrderItem->handle($crudOptions);
    }

    public function testCreateOrderItemSuccessfully(): void
    {
        $dto = $this->createMock(CreateOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getProductId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(2);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $customerOrder = $this->createMock(CustomerOrder::class);
        $product = $this->createMock(Product::class);
        $product->method('getSellPrice')->willReturn('10.00');
        $product->method('getSellPriceIncVat')->willReturn('12.00');
        $product->method('getWeight')->willReturn(100);

        $this->entityManager->method('getRepository')->willReturnMap([
            [CustomerOrder::class, $this->createMock(CustomerOrderRepository::class)],
            [Product::class, $this->createMock(ProductRepository::class)]
        ]);

        $this->entityManager->getRepository(CustomerOrder::class)->method('find')->willReturn($customerOrder);
        $this->entityManager->getRepository(Product::class)->method('find')->willReturn($product);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
        $this->domainEventDispatcher->expects($this->once())->method('dispatchProviderEvents');

        $customerOrderItem = $this->createOrderItem->fromDto($dto);
        $this->assertInstanceOf(CustomerOrderItem::class, $customerOrderItem);
    }
}