<?php

namespace App\Tests\Unit\Service\Order;

use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use App\Order\Application\DTO\CreateOrderDto;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Infrastructure\Persistence\Doctrine\VatRateDoctrineRepository;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\CreateOrder;
use App\Shared\Domain\ValueObject\ShippingMethod;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class CreateOrderTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private CreateOrder $createOrder;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->createOrder = new CreateOrder($this->em, $this->validator);
    }

    public function testHandleWithNonCreateOrderDtoEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of CreateOrderDto');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->createOrder)($context);
    }

    public function testCreateOrderSuccessfully(): void
    {
        $dto = $this->createMock(CreateOrderDto::class);
        $dto->method('getCustomerId')->willReturn(1);
        $dto->method('getShippingMethod')->willReturn(ShippingMethod::NEXT_DAY);
        $dto->method('getCustomerOrderRef')->willReturn('order_ref');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($dto);

        $user = $this->createMock(User::class);
        $user->method('getShippingAddress')->willReturn($this->createMock(Address::class));
        $user->method('getBillingAddress')->willReturn($this->createMock(Address::class));

        $vatRate = $this->createMock(VatRate::class);
        $vatRate->method('getRate')->willReturn('20.00');

        $this->em->method('getRepository')->willReturnMap([
            [User::class, $this->createMock(UserDoctrineRepository::class)],
            [VatRate::class, $this->createMock(VatRateDoctrineRepository::class)]
        ]);

        $this->em->getRepository(User::class)->method('find')->willReturn($user);
        $this->em->getRepository(VatRate::class)->method('findOneBy')->willReturn($vatRate);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

        $customerOrder = $this->createOrder->fromDto($dto);
        $this->assertInstanceOf(CustomerOrder::class, $customerOrder);
    }
}
