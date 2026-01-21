<?php

namespace App\Tests\Order\Infrastructure;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Repository\OrderItemRepository;
use App\Tests\Shared\Factory\CustomerOrderItemFactory;
use App\Tests\Shared\Factory\ProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class OrderItemUpdateDeleteMappingTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    private OrderItemRepository $orderItems;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
        $this->orderItems = self::getContainer()->get(OrderItemRepository::class);
    }

    public function testUpdateRoundTripPersistsChanges(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
            'quantity' => 5,
        ]);

        $this->em->flush();
        $this->em->clear();

        $id = $orderItem->getId();
        $loaded = $this->em->getRepository(CustomerOrderItem::class)->find($id);
        self::assertNotNull($loaded);
        self::assertSame(5, $loaded->getQuantity());

        $loaded->updateItem(
            quantity: 10,
            price: $loaded->getPrice(),
            priceIncVat: $loaded->getPriceIncVat(),
            weight: $loaded->getWeight(),
        );

        $this->em->flush();
        $this->em->clear();

        $reloaded = $this->em->getRepository(CustomerOrderItem::class)->find($id);
        self::assertNotNull($reloaded);
        self::assertSame(10, $reloaded->getQuantity());
        self::assertSame(bcmul('10', $reloaded->getPrice(), 2), $reloaded->getTotalPrice());
    }

    public function testDeleteRemovesRow(): void
    {
        $product = ProductFactory::new()->withActiveSource()->create();
        $orderItem = CustomerOrderItemFactory::createOne([
            'product' => $product,
        ]);

        $this->em->flush();

        $id = $orderItem->getId();

        $loaded = $this->em->getRepository(CustomerOrderItem::class)->find($id);
        self::assertNotNull($loaded);

        $this->orderItems->remove($loaded);
        $this->em->flush();
        $this->em->clear();

        self::assertNull($this->em->getRepository(CustomerOrderItem::class)->find($id));
    }
}
