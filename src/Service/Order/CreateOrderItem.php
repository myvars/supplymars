<?php

namespace App\Service\Order;

use App\DTO\CreateOrderItemDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateOrderItem implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $dto = $crudOptions->getEntity();
        if (!$dto instanceof CreateOrderItemDto) {
            throw new \InvalidArgumentException('Entity must be an instance of CreateOrderItemDto');
        }

        $this->fromDto($dto);
    }

    public function fromDto(CreateOrderItemDto $dto): CustomerOrderItem
    {
        $customerOrder = $this->getCustomerOrder($dto->getId());
        $product = $this->getProduct($dto->getProductId());

        $customerOrderItem = CustomerOrderItem::createFromProduct(
            $customerOrder,
            $product,
            $dto->getQuantity()
        );

        $errors = $this->validator->validate($customerOrderItem);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->persist($customerOrderItem);
        $this->entityManager->flush();

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
