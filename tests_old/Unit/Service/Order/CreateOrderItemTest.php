<?php

namespace App\Tests\Unit\Service\Order;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Infrastructure\Persistence\Doctrine\ProductDoctrineRepository;
use App\Order\Application\DTO\CreateOrderItemDto;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Infrastructure\Persistence\Doctrine\CustomerOrderDoctrineRepository;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\CreateOrderItem;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateOrderItemTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private CreateOrderItem $createOrderItem;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->createOrderItem = new CreateOrderItem($this->em, $this->validator);
    }

    public function testHandleWithNonCreateOrderItemDtoEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of CreateOrderItemDto');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->createOrderItem)($context);
    }

    public function testCreateOrderItemSuccessfully(): void
    {
        $dto = $this->createMock(CreateOrderItemDto::class);
        $dto->method('getId')->willReturn(1);
        $dto->method('getProductId')->willReturn(1);
        $dto->method('getQuantity')->willReturn(2);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $customerOrder = $this->createMock(CustomerOrder::class);
        $product = $this->createMock(Product::class);
        $product->method('getSellPrice')->willReturn('10.00');
        $product->method('getSellPriceIncVat')->willReturn('12.00');
        $product->method('getWeight')->willReturn(100);

        $this->em->method('getRepository')->willReturnMap([
            [CustomerOrder::class, $this->createMock(CustomerOrderDoctrineRepository::class)],
            [Product::class, $this->createMock(ProductDoctrineRepository::class)]
        ]);

        $this->em->getRepository(CustomerOrder::class)->method('find')->willReturn($customerOrder);
        $this->em->getRepository(Product::class)->method('find')->willReturn($product);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $this->createOrderItem->fromDto($dto);
    }
}
