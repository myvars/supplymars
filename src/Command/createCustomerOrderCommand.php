<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use App\Entity\ShippingMethod;
use App\Entity\User;
use App\Entity\VatRate;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-customer-order',
    description: 'Create new customer order',
)]
class createCustomerOrderCommand extends Command
{
    public const DEFAULT_USER = 'adam@admin.com';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$defaultUser = $this->getDefaultUser()) {
            $io->error('Default user found');

            return Command::FAILURE;
        }

        if (!$billingAddress = $this->getDefaultBillingAddress($defaultUser)) {
            $billingAddress = new Address();
            $billingAddress->setCustomer($defaultUser);
            $billingAddress->setEmail($defaultUser->getEmail());
            $billingAddress->setFullName($defaultUser->getFullName());
            $billingAddress->setStreet('123 Main Street');
            $billingAddress->setCounty('Springfield');
            $billingAddress->setCity('Springfield');
            $billingAddress->setPostcode('12345');
            $billingAddress->setCountry('UK');
            $billingAddress->setDefaultBillingAddress(true);
        }

        if (!$shippingAddress = $this->getDefaultShippingAddress($defaultUser)) {
            $billingAddress->setDefaultShippingAddress(true);
            $shippingAddress = $billingAddress;
        }

        $order = new CustomerOrder();
        $order->setCustomer($defaultUser);
        $order->setBillingAddress($billingAddress);
        $order->setShippingAddress($shippingAddress);
        $order->setShippingDetailsFromShippingMethod(
            ShippingMethod::NEXT_DAY,
            $this->getDefaultVatRate()
        );
        $customerOrderItem = new CustomerOrderItem();
        $customerOrderItem->createFromProduct($this->getRandomProduct());
        $order->addCustomerOrderItem($customerOrderItem);

        $customerOrderItem2 = new CustomerOrderItem();
        $customerOrderItem2->createFromProduct($this->getRandomProduct());
        $order->addCustomerOrderItem($customerOrderItem2);

        $this->entityManager->persist($billingAddress);
        $this->entityManager->persist($shippingAddress);
        $this->entityManager->persist($order);
        $this->entityManager->persist($customerOrderItem);
        $this->entityManager->persist($customerOrderItem2);

        $this->entityManager->flush();

        $io->success('Customer order created successfully');

        return Command::SUCCESS;
    }

    private function getDefaultUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::DEFAULT_USER]);
    }

    private function getDefaultVatRate(): VatRate
    {
        return $this->entityManager->getRepository(VatRate::class)->findOneBy(['isDefaultVatRate' => true]);
    }

    private function getRandomProduct(): Product
    {
        return $this->entityManager->getRepository(Product::class)->findOneBy(['id' => rand(1,30)]);
    }

    private function getDefaultBillingAddress(User $defaultUser): ?Address
    {
        return $this->entityManager->getRepository(Address::class)->findDefaultBillingAddress($defaultUser);
    }

    private function getDefaultShippingAddress(User $defaultUser): ?Address
    {
        return $this->entityManager->getRepository(Address::class)->findDefaultShippingAddress($defaultUser);
    }
}
