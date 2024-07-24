<?php

namespace App\Service\Order;

use App\DTO\CreateOrderDto;
use App\Entity\Address;
use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Entity\VatRate;
use App\Service\Crud\Core\CrudActionInterface;
use App\Service\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class CreateOrder implements CrudActionInterface
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
        private readonly DomainEventDispatcher $domainEventDispatcher
    ) {
    }

    public function handle(object $entity, ?array $context): void
    {
        assert($entity instanceof CreateOrderDto);

        $this->fromDto($entity);
    }

    public function fromDto(CreateOrderDto $dto): CustomerOrder
    {
        $customer = $this->getCustomer($dto->getCustomerId());
        $billingAddress = $this->getBillingAddress($customer);
        $shippingAddress = $this->getShippingAddress($customer);
        $vatRate = $this->getDefaultVatRate();

        $customerOrder = (new CustomerOrder())
            ->setCustomer($customer)
            ->setCustomerOrderRef($dto->getCustomerOrderRef())
            ->setBillingAddress($billingAddress)
            ->setShippingAddress($shippingAddress)
            ->setShippingDetailsFromShippingMethod($dto->getShippingMethod(), $vatRate);

        $errors = $this->validator->validate($customerOrder);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string)$errors);
        }

        $this->entityManager->persist($customerOrder);
        $this->entityManager->flush();

        $this->domainEventDispatcher->dispatchProviderEvents($customerOrder);

        return $customerOrder;
    }

    private function getCustomer(int $id): User
    {
        return $this->entityManager->getRepository(User::class)->find($id);
    }

    private function getBillingAddress(User $customer): Address
    {
        return $this->entityManager->getRepository(Address::class)->findDefaultBillingAddress($customer);
    }

    private function getShippingAddress(User $customer): Address
    {
        return $this->entityManager->getRepository(Address::class)->findDefaultShippingAddress($customer);
    }

    private function getDefaultVatRate(): VatRate
    {
        return $this->entityManager->getRepository(VatRate::class)->findOneBy(['isDefaultVatRate' => true]);
    }
}