<?php

namespace App\Strategy;

use App\DTO\OrderCreateDto;
use App\Entity\Address;
use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Entity\VatRate;
use App\Service\Crud\Core\CrudCreateStrategyInterface;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;
use Symfony\Component\Validator\Validator\ValidatorInterface;

#[AsAlias('app.crud.order.create.strategy')]
final class CrudOrderCreateStrategy implements CrudCreateStrategyInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator
    ) {
    }

    public function create(object $entity, ?array $context): void
    {
        assert($entity instanceof OrderCreateDto);

        $customer = $this->entityManager->getRepository(User::class)->find($entity->getCustomerId());
        $billingAddress = $this->entityManager->getRepository(Address::class)->findDefaultBillingAddress($customer);
        $shippingAddress = $this->entityManager->getRepository(Address::class)->findDefaultShippingAddress($customer);
        $vatRate = $this->entityManager->getRepository(VatRate::class)->findOneBy(['isDefaultVatRate' => true]);

        $customerOrder = (new CustomerOrder())
            ->setCustomer($customer)
            ->setCustomerOrderRef($entity->getCustomerOrderRef())
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setShippingDetailsFromShippingMethod($entity->getShippingMethod(), $vatRate);

        $errors = $this->validator->validate($customerOrder);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string)$errors);
        }

        $this->entityManager->persist($customerOrder);
        $this->entityManager->flush();
    }
}