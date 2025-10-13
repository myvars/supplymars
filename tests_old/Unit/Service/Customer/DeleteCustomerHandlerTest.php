<?php

namespace App\Tests\Unit\Service\Customer;

use App\Customer\Application\Handler\DeleteCustomerHandler;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Service\Crud\Common\CrudContext;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;

class DeleteCustomerHandlerTest extends TestCase
{
    private MockObject $em;

    private DeleteCustomerHandler $handler;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->customers = $this->createMock(UserRepository::class);
        $this->handler = new DeleteCustomerHandler($this->customers, $this->em);
    }

    public function testHandleWithNonUserEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of User');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->handler)($context);
    }

    public function testHandleWithNonDeletableCustomer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Customer cannot be deleted');

        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('isDeletable')->willReturn(false);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($user);

        $this->customers->method('get')->willReturn($user);

        ($this->handler)($context);
    }

    public function testHandleWithValidCustomer(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getId')->willReturn(1);
        $user->method('isDeletable')->willReturn(true);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($user);

        $this->customers->method('get')->willReturn($user);
        $this->customers->expects($this->once())->method('remove')->with($user);
        $this->em->expects($this->once())->method('flush');

        ($this->handler)($context);
    }
}
