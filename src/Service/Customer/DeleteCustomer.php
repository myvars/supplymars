<?php

namespace App\Service\Customer;

use App\Entity\User;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use Doctrine\ORM\EntityManagerInterface;

final readonly class DeleteCustomer implements CrudActionInterface
{
    public function __construct(private EntityManagerInterface $entityManager)
    {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $customer = $crudOptions->getEntity();
        if (!$customer instanceof User) {
            throw new \InvalidArgumentException('Entity must be an instance of User');
        }

        $this->fromCustomer($customer);
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
