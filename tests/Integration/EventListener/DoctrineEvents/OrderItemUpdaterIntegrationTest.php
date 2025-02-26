<?php

namespace App\Tests\Integration\EventListener\DoctrineEvents;

use App\DTO\EditOrderItemDto;
use App\Entity\CustomerOrder;
use App\Factory\CustomerOrderItemFactory;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Order\EditOrderItem;
use App\Service\Product\MarkupCalculator;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class OrderItemUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $entityManager;
    private EditOrderItem $editOrderItem;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $markupCalculator = static::getContainer()->get(MarkupCalculator::class);
        $domainEventDispatcher = static::getContainer()->get(DomainEventDispatcher::class);
        $this->editOrderItem = new EditOrderItem($this->entityManager, $markupCalculator, $domainEventDispatcher);
    }

    public function testPreAndPostUpdateRecalculatesTotalWhenFieldsChange(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne()->_real();
        $customerOrder = $customerOrderItem->getCustomerOrder();
        $customerOrderTotalPrice = $customerOrder->getTotalPrice();

        $dto = new EditOrderItemDto(
            $customerOrderItem->getId(),
            2,
            '100.00'
        );

        $crudOptions = new CrudOptions();
        $crudOptions->setEntity($dto);

        $this->editOrderItem->handle($crudOptions);

        $updatedCustomerOrder = $this->entityManager->getRepository(CustomerOrder::class)->find($customerOrder->getId());
        $this->assertNotSame($customerOrderTotalPrice, $updatedCustomerOrder->getTotalPrice());
    }
}