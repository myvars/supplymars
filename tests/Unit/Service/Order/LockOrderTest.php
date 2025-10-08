<?php

namespace App\Tests\Unit\Service\Order;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\LockOrder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class LockOrderTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $security;

    private LockOrder $lockOrder;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->lockOrder = new LockOrder($this->entityManager, $this->security);
    }

    public function testHandleWithNonCustomerOrderEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of CustomerOrder');

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn(new \stdClass());

        $this->lockOrder->handle($crudOptions);
    }

    public function testToggleStatusSuccessfully(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $user = $this->createMock(User::class);

        $crudOptions = $this->createMock(CrudOptions::class);
        $crudOptions->method('getEntity')->willReturn($customerOrder);

        $this->security->method('getUser')->willReturn($user);

        $customerOrder->expects($this->once())->method('lockOrder')->with($user);
        $this->entityManager->expects($this->once())->method('flush');

        $this->lockOrder->handle($crudOptions);
    }
}