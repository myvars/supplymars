<?php

namespace App\Service\Order;

use App\DTO\OrderItemEditDto;
use App\Entity\CustomerOrderItem;
use App\Service\Product\MarkupCalculator;
use Doctrine\ORM\EntityManagerInterface;

final class OrderItemUpdater
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MarkupCalculator $markupCalculator
    ) {
    }

    public function updateFromDto(OrderItemEditDto $dto, bool $flush = true): void
    {
        $customerOrderItem =  $this->getCustomerOrderItem($dto->getId());

        $price = $this->markupCalculator->calculateSellPriceBeforeVat(
            $dto->getPriceIncVat(),
            $customerOrderItem->getVatRate()->getRate()
        );

        if ($dto->getQuantity() === 0) {
            dd($customerOrderItem);
            $this->removeCustomerOrderItem($customerOrderItem);
        } else {
            $customerOrderItem->updateItem($dto->getQuantity(), $price, $dto->getPriceIncVat());
        }
        $customerOrderItem->getCustomerOrder()->recalculateTotal();

        $this->entityManager->persist($customerOrderItem);
        if ($flush) {
            $this->entityManager->flush();
        }
    }

    private function getCustomerOrderItem(int $id): CustomerOrderItem
    {
        return $this->entityManager->getRepository(CustomerOrderItem::class)->find($id);
    }

    private function removeCustomerOrderItem(CustomerOrderItem $customerOrderItem): void
    {
        if ($customerOrderItem->getQtyAddedToPurchaseOrders()) {
            throw new \LogicException('Cannot remove item that has been added to purchase orders');
        }

        $this->entityManager->remove($customerOrderItem);

    }
}