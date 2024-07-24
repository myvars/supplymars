<?php

namespace App\Service\Order;

use App\DTO\CreateOrderItemDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use App\Service\Crud\Core\CrudActionInterface;
use App\Service\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateOrderItem implements CrudActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly DomainEventDispatcher $domainEventDispatcher
    ) {
    }

    public function handle(object $entity, ?array $context): void
    {
        assert($entity instanceof CreateOrderItemDto);

        $this->fromDto($entity);
    }

    public function fromDto(CreateOrderItemDto $dto): CustomerOrderItem
    {
        $customerOrder = $this->getCustomerOrder($dto->getId());
        $product = $this->getProduct($dto->getProductId());

        $customerOrderItem = (new CustomerOrderItem())
            ->setCustomerOrder($customerOrder)
            ->createFromProduct($product, $dto->getQuantity());

        $errors = $this->validator->validate($customerOrderItem);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string)$errors);
        }

        $customerOrder->addCustomerOrderItem($customerOrderItem);

        $this->entityManager->persist($customerOrderItem);
        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents([$customerOrderItem, $customerOrder]);

        return $customerOrderItem;
    }

    private function getCustomerOrder(int $id): CustomerOrder
    {
        return $this->entityManager->getRepository(CustomerOrder::class)->find($id);
    }

    private function getProduct(int $id): Product
    {
        return $this->entityManager->getRepository(Product::class)->find($id);
    }
}