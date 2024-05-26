<?php

namespace App\Service\Order;

use App\DTO\OrderItemCreateDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class OrderItemCreator
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function createFromDto(OrderItemCreateDto $dto, bool $flush = true): CustomerOrderItem
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
        if ($flush) {
            $this->entityManager->flush();
        }

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