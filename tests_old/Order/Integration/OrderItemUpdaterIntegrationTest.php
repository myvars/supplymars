<?php

namespace App\Tests\Order\Integration;

use App\Order\Application\DTO\EditOrderItemDto;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Service\Crud\Common\CrudContext;
use App\Service\Order\EditOrderItem;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\CustomerOrderItemFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class OrderItemUpdaterIntegrationTest extends KernelTestCase
{
    use Factories;

    private EntityManagerInterface $em;

    private EditOrderItem $editOrderItem;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $markupCalculator = static::getContainer()->get(MarkupCalculator::class);
        $this->editOrderItem = new EditOrderItem($this->em, $markupCalculator);
    }

    public function testPreAndPostUpdateRecalculatesTotalWhenFieldsChange(): void
    {
        $customerOrderItem = CustomerOrderItemFactory::createOne();
        $customerOrder = $customerOrderItem->getCustomerOrder();
        $customerOrderTotalPrice = $customerOrder->getTotalPrice();

        $dto = new EditOrderItemDto(
            $customerOrderItem->getId(),
            2,
            '100.00'
        );

        $context = new CrudContext();
        $context->setEntity($dto);

        ($this->editOrderItem)($context);

        $updatedCustomerOrder = $this->em->getRepository(CustomerOrder::class)->find($customerOrder->getId());
        $this->assertNotSame($customerOrderTotalPrice, $updatedCustomerOrder->getTotalPrice());
    }
}
