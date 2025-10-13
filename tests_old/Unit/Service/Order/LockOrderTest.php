<?php

namespace App\Tests\Unit\Service\Order;

use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\LockOrder;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;

class LockOrderTest extends TestCase
{
    private MockObject $em;

    private MockObject $security;

    private LockOrder $lockOrder;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->security = $this->createMock(Security::class);
        $this->lockOrder = new LockOrder($this->em, $this->security);
    }

    public function testHandleWithNonCustomerOrderEntity(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Entity must be an instance of CustomerOrder');

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn(new \stdClass());

        ($this->lockOrder)($context);
    }

    public function testToggleStatusSuccessfully(): void
    {
        $customerOrder = $this->createMock(CustomerOrder::class);
        $user = $this->createMock(User::class);

        $context = $this->createMock(CrudContext::class);
        $context->method('getEntity')->willReturn($customerOrder);

        $this->security->method('getUser')->willReturn($user);

        $customerOrder->expects($this->once())->method('lockOrder')->with($user);
        $this->em->expects($this->once())->method('flush');

        ($this->lockOrder)($context);
    }
}
