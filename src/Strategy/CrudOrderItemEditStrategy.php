<?php

namespace App\Strategy;

use App\DTO\OrderItemEditDto;
use App\Entity\CustomerOrderItem;
use App\Service\Crud\Core\CrudUpdateStrategyInterface;
use App\Service\Product\MarkupCalculator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias('app.crud.order.item.edit.strategy')]
final class CrudOrderItemEditStrategy implements CrudUpdateStrategyInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly MarkupCalculator $markupCalculator
    ) {
    }

    public function update(object $entity, ?array $context): void
    {
        assert($entity instanceof OrderItemEditDto);
        assert($context['customerOrderItem'] instanceof CustomerOrderItem);

        $price = $this->markupCalculator->calculateSellPriceBeforeVat(
            $entity->getPriceIncVat(),
            $context['customerOrderItem']->getProduct()->getCategory()->getVatRate()->getRate()
        );

        $context['customerOrderItem']->updateItem(
            $entity->getQuantity(),
            $price,
            $entity->getPriceIncVat(),
        );

        $context['customerOrderItem']->getCustomerOrder()->recalculateTotal();

        $this->entityManager->persist($context['customerOrderItem']);
        $this->entityManager->flush();
    }
}