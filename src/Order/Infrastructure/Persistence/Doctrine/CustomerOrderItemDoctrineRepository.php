<?php

namespace App\Order\Infrastructure\Persistence\Doctrine;

use App\Order\Domain\Model\Order\CustomerOrderItem;
use App\Order\Domain\Model\Order\OrderItemId;
use App\Order\Domain\Model\Order\OrderItemPublicId;
use App\Order\Domain\Repository\OrderItemRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<CustomerOrderItem>
 *
 * @method CustomerOrderItem|null find($id, $lockMode = null, $lockVersion = null)
 * @method CustomerOrderItem|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method CustomerOrderItem[]    findAll()
 * @method CustomerOrderItem[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class CustomerOrderItemDoctrineRepository extends ServiceEntityRepository implements OrderItemRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, CustomerOrderItem::class);
    }

    public function add(CustomerOrderItem $orderItem): void
    {
        $this->getEntityManager()->persist($orderItem);
    }

    public function remove(CustomerOrderItem $orderItem): void
    {
        $this->getEntityManager()->remove($orderItem);
    }

    public function get(OrderItemId $id): ?CustomerOrderItem
    {
        return $this->find($id->value());
    }

    public function getByPublicId(OrderItemPublicId $publicId): ?CustomerOrderItem
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }
}
