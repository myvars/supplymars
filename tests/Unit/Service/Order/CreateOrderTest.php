<?php

namespace App\Tests\Unit\Service\Order;

use App\DTO\CreateOrderDto;
use App\Entity\Address;
use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Entity\VatRate;
use App\Enum\ShippingMethod;
use App\Repository\UserRepository;
use App\Repository\VatRateRepository;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\CreateOrder;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateOrderTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private ValidatorInterface $validator;
    private DomainEventDispatcher $domainEventDispatcher;
    private CreateOrder $createOrder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->domainEventDispatcher = $this->createMock(DomainEventDispatcher::class);
        $this->createOrder = new CreateOrder($this->entityManager, $this->validator, $this->domainEventDispatcher);
    }

    public function testHandleWithNonCreateOrderDtoEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of CreateOrderDto');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->createOrder->handle($crudOptions);
    }

    public function testCreateOrderSuccessfully(): void
    {
        $dto = $this->createMock(CreateOrderDto::class);
        $dto->method('getCustomerId')->willReturn(1);
        $dto->method('getShippingMethod')->willReturn(ShippingMethod::NEXT_DAY);
        $dto->method('getCustomerOrderRef')->willReturn('order_ref');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($dto);

        $user = $this->createMock(User::class);
        $user->method('getShippingAddress')->willReturn($this->createMock(Address::class));
        $user->method('getBillingAddress')->willReturn($this->createMock(Address::class));

        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20.00');

        $this->entityManager->method('getRepository')->willReturnMap([
            [User::class, $this->createMock(UserRepository::class)],
            [VatRate::class, $this->createMock(VatRateRepository::class)]
        ]);

        $this->entityManager->getRepository(User::class)->method('find')->willReturn($user);
        $this->entityManager->getRepository(VatRate::class)->method('findOneBy')->willReturn($vatRate);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');
        $this->domainEventDispatcher->expects($this->once())->method('dispatchProviderEvents');

        $customerOrder = $this->createOrder->fromDto($dto);
        $this->assertInstanceOf(CustomerOrder::class, $customerOrder);
    }
}