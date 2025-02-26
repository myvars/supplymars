<?php

namespace App\Tests\Unit\Service\Customer;

use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Customer\DeleteCustomer;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

class DeleteCustomerTest extends TestCase
{
    private EntityManagerInterface $entityManager;
    private DeleteCustomer $deleteCustomer;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->deleteCustomer = new DeleteCustomer($this->entityManager);
    }

    public function testHandleWithNonUserEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of User');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->deleteCustomer->handle($crudOptions);
    }

    public function testHandleWithAdminUser(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Admin user cannot be deleted');

        $user = $this->createMock(User::class);
        $user->method('isAdmin')->willReturn(true);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($user);

        $this->deleteCustomer->handle($crudOptions);
    }

    public function testHandleWithCustomerOrders(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer has order history and cannot be deleted');

        $user = $this->createMock(User::class);
        $user->method('isAdmin')->willReturn(false);
        $user->method('getCustomerOrders')->willReturn(new ArrayCollection([$this->createMock(CustomerOrder::class)]));

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($user);

        $this->deleteCustomer->handle($crudOptions);
    }

    public function testHandleWithValidCustomer(): void
    {
        $user = $this->createMock(User::class);
        $user->method('isAdmin')->willReturn(false);
        $user->method('getCustomerOrders')->willReturn(new ArrayCollection());
        $user->method('getAddresses')->willReturn(new ArrayCollection());

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($user);

        $this->entityManager->expects($this->once())->method('remove')->with($user);
        $this->entityManager->expects($this->once())->method('flush');

        $this->deleteCustomer->handle($crudOptions);
    }
}