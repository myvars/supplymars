<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\User;
use App\Factory\AddressFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-address',
    description: 'Create new customer address',
)]
class createAddressCommand extends Command
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

        $address = AddressFactory::createOne([
            'customer' => $defaultUser,
            'email' => $defaultUser->getEmail(),
            'fullName' => $defaultUser->getFullName(),
        ])->object();

        if (!$this->getDefaultShippingAddress($defaultUser)) {
            $address->setDefaultShippingAddress(true);
        }

        if (!$this->getDefaultBillingAddress($defaultUser)) {
            $address->setDefaultBillingAddress(true);
        }

        $this->entityManager->persist($address);
        $this->entityManager->flush();

        $io->success('Address created successfully');

        return Command::SUCCESS;
    }

    private function getDefaultUser(): User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::DEFAULT_USER]);
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
