<?php

namespace App\Service\Order;

use App\DTO\EditOrderItemDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Service\Crud\Core\CrudActionInterface;
use App\Service\Product\MarkupCalculator;
use Doctrine\ORM\EntityManagerInterface;

final class EditOrderItem implements CrudActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MarkupCalculator $markupCalculator
    ) {
    }

    public function handle(object $entity, ?array $context): void
    {
        assert($entity instanceof EditOrderItemDto);
        $this->fromDto($entity);
    }

    public function fromDto(EditOrderItemDto $dto, bool $flush = true): void
    {
        $customerOrderItem =  $this->getCustomerOrderItem($dto->getId());
        $customerOrder = $this->getCustomerOrder($customerOrderItem);

        if ($dto->getQuantity() < $customerOrderItem->getQtyAddedToPurchaseOrders()) {
            return;
        }

        $price = $this->markupCalculator->calculateSellPriceBeforeVat(
            $dto->getPriceIncVat(),
            $customerOrderItem->getVatRate()->getRate()
        );

        if ($dto->getQuantity() === 0) {
            $this->removeCustomerOrderItem($customerOrderItem);
        } else {
            $customerOrderItem->updateItem($dto->getQuantity(), $price, $dto->getPriceIncVat());
            $this->entityManager->persist($customerOrderItem);
        }
        $customerOrder->recalculateTotal();

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
        $customerOrder = $this->getCustomerOrder($customerOrderItem);
        $customerOrder->removeCustomerOrderItem($customerOrderItem);
        $this->entityManager->persist($customerOrder);

        $this->entityManager->remove($customerOrderItem);
    }

    private function getCustomerOrder(CustomerOrderItem $customerOrderItem): CustomerOrder
    {
        return $customerOrderItem->getCustomerOrder();
    }
}