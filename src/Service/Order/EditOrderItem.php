<?php

namespace App\Service\Order;

use App\DTO\EditOrderItemDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use App\Service\DomainEventDispatcher;
use App\Service\Product\MarkupCalculator;
use Doctrine\ORM\EntityManagerInterface;

final readonly class EditOrderItem implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private MarkupCalculator $markupCalculator,
        private DomainEventDispatcher $domainEventDispatcher
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $dto = $crudOptions->getEntity();
        if (!$dto instanceof EditOrderItemDto) {
            throw new \InvalidArgumentException('Entity must be instance of EditOrderItemDto');
        }

        $this->fromDto($dto);
    }

    public function fromDto(EditOrderItemDto $dto): void
    {
        $customerOrderItem = $this->getCustomerOrderItem($dto->getId());
        $customerOrder = $this->getCustomerOrder($customerOrderItem);

        if ($dto->getQuantity() < $customerOrderItem->getQtyAddedToPurchaseOrders()) {
            return;
        }

        $this->editCustomerOrderItem($customerOrderItem, $dto->getQuantity(), $dto->getPriceIncVat());
        $customerOrder->recalculateTotal();

        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents([
            $customerOrderItem,
            $customerOrder
        ]);
    }

    private function getCustomerOrderItem(int $id): CustomerOrderItem
    {
        return $this->entityManager->getRepository(CustomerOrderItem::class)->find($id);
    }

    private function getCustomerOrder(CustomerOrderItem $customerOrderItem): CustomerOrder
    {
        return $customerOrderItem->getCustomerOrder();
    }

    private function editCustomerOrderItem(CustomerOrderItem $customerOrderItem, int $qty, string $priceIncVat): void
    {
        if ($qty === 0) {
            $this->removeCustomerOrderItem($customerOrderItem);

            return;
        }

        $this->updateCustomerOrderItem($customerOrderItem, $qty, $priceIncVat);
    }

    private function removeCustomerOrderItem(CustomerOrderItem $customerOrderItem): void
    {
        $customerOrder = $this->getCustomerOrder($customerOrderItem);
        $customerOrder->removeCustomerOrderItem($customerOrderItem);

        $this->entityManager->persist($customerOrder);

        $this->entityManager->remove($customerOrderItem);
    }

    private function updateCustomerOrderItem(CustomerOrderItem $customerOrderItem, int $qty, string $priceIncVat): void
    {
        $price = $this->markupCalculator->calculateSellPriceBeforeVat(
            $priceIncVat,
            $customerOrderItem->getVatRate()->getRate()
        );
        $customerOrderItem->updateItem($qty, $price, $priceIncVat);

        $this->entityManager->persist($customerOrderItem);
    }
}