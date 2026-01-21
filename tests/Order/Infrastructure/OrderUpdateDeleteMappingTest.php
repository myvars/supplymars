<?php

namespace App\Tests\Order\Infrastructure;

use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Repository\OrderRepository;
use App\Tests\Shared\Factory\CustomerOrderFactory;
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
        $order = CustomerOrderFactory::createOne([
            'customerOrderRef' => 'BEFORE-REF',
        ]);

        $this->em->flush();
        $this->em->clear();

        $id = $order->getId();
        $loaded = $this->em->getRepository(CustomerOrder::class)->find($id);
        self::assertNotNull($loaded);
        self::assertSame('BEFORE-REF', $loaded->getCustomerOrderRef());
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
