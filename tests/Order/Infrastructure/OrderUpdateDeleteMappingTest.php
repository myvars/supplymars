<?php

namespace App\Tests\Order\Infrastructure;

use App\Customer\Domain\Model\User\User;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Tests\Shared\Factory\CustomerOrderFactory;
use App\Tests\Shared\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class OrderUpdateDeleteMappingTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    private OrderRepository $orders;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->orders = self::getContainer()->get(OrderRepository::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $staff = UserFactory::new()->asStaff()->create();
        $order = CustomerOrderFactory::createOne([
            'customerOrderRef' => 'BEFORE-REF',
        ]);

        $this->em->flush();
        $this->em->clear();

        $id = $order->getId();
        $loaded = $this->em->getRepository(CustomerOrder::class)->find($id);
        self::assertNotNull($loaded);
        self::assertNull($loaded->getOrderLock());

        $staffUser = $this->em->getRepository(User::class)->find($staff->getId());
        $loaded->lockOrder($staffUser);

        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->getRepository(CustomerOrder::class)->find($id);
        self::assertNotNull($reloaded);
        self::assertNotNull($reloaded->getOrderLock());
        self::assertSame($staff->getId(), $reloaded->getOrderLock()->getId());
    }

    public function testDeleteRemovesRow(): void
    {
        $order = CustomerOrderFactory::createOne();

        $this->em->flush();

        $id = $order->getId();

        $loaded = $this->em->getRepository(CustomerOrder::class)->find($id);
        self::assertNotNull($loaded);

        $this->orders->remove($loaded);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(CustomerOrder::class)->find($id));
    }
}
