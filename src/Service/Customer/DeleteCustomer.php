<?php

namespace App\Service\Customer;

use App\Entity\User;
use App\Service\Crud\Core\CrudActionInterface;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DeleteCustomer implements CrudActionInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function handle(object $entity, ?array $context): void
    {
        assert($entity instanceof User);

        $this->fromCustomer($entity);
    }

    public function fromCustomer(User $customer): void
    {
        if ($customer->isAdmin()) {
            throw new \InvalidArgumentException('Admin user cannot be deleted');
        }

        if (!$customer->getCustomerOrders()->isEmpty()) {
            throw new \InvalidArgumentException('Customer has order history and cannot be deleted');
        }

        foreach ($customer->getAddresses() as $address) {

            $customer->removeAddress($address);
            $this->entityManager->remove($address);
        }

        $this->entityManager->remove($customer);
        $this->entityManager->flush();
    }
}