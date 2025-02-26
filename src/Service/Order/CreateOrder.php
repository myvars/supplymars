<?php

namespace App\Service\Order;

use App\DTO\CreateOrderDto;
use App\Entity\CustomerOrder;
use App\Entity\User;
use App\Entity\VatRate;
use App\Service\Crud\Common\CrudActionInterface;
use App\Service\Crud\Common\CrudOptions;
use App\Service\Utility\DomainEventDispatcher;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreateOrder implements CrudActionInterface
{
    public function __construct(
        private EntityManagerInterface $entityManager,
        private ValidatorInterface $validator,
        private DomainEventDispatcher $domainEventDispatcher,
    ) {
    }

    public function handle(CrudOptions $crudOptions): void
    {
        $dto = $crudOptions->getEntity();
        if (!$dto instanceof CreateOrderDto) {
            throw new \InvalidArgumentException('Entity must be an instance of CreateOrderDto');
        }

        $this->fromDto($dto);
    }

    public function fromDto(CreateOrderDto $dto): CustomerOrder
    {
        $customer = $this->getCustomer($dto->getCustomerId());
        $vatRate = $this->getDefaultVatRate();

        $customerOrder = CustomerOrder::createFromCustomer(
            $customer,
            $dto->getShippingMethod(),
            $vatRate,
            $dto->getCustomerOrderRef()
        );

        $errors = $this->validator->validate($customerOrder);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
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

    private function getDefaultVatRate(): VatRate
    {
        return $this->entityManager->getRepository(VatRate::class)->findOneBy(['isDefaultVatRate' => true]);
    }
}
