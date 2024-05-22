<?php

namespace App\Strategy;

use App\DTO\OrderItemCreateDto;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use App\Service\Crud\Core\CrudCreateStrategyInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsAlias('app.crud.order.item.create.strategy')]
final class CrudOrderItemCreateStrategy implements CrudCreateStrategyInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function create(object $entity, ?array $context): void
    {
        assert($entity instanceof OrderItemCreateDto);
        assert($context['customerOrder'] instanceof CustomerOrder);

        $product = $this->entityManager->getRepository(Product::class)->find($entity->getProductId());

        $customerOrderItem = (new CustomerOrderItem())
            ->setCustomerOrder($context['customerOrder'])
            ->createFromProduct($product, $entity->getQuantity());

        $errors = $this->validator->validate($customerOrderItem);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string)$errors);
        }

        $context['customerOrder']->addCustomerOrderItem($customerOrderItem);
        $this->entityManager->persist($customerOrderItem);
        $this->entityManager->flush();
    }
}