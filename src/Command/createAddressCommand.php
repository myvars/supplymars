<?php

namespace App\Command;

use App\Entity\Address;
use App\Entity\User;
use App\Factory\AddressFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-address',
    description: 'Create new customer address',
)]
class createAddressCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('email', InputArgument::REQUIRED, 'User email');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $email = $input->getArgument('email');

        if (!$email) {
            $io->note('Please provide an email');

            return Command::FAILURE;
        }

        $user = $this->getUser($email);
        if (!$user instanceof User) {
            $io->error(sprintf('User with email \'%s\' not found', $email));

            return Command::FAILURE;
        }

        $billingAddress = $this->getDefaultBillingAddress($user);
        if (!$billingAddress instanceof Address) {
            $billingAddress = $this->createBillingAddress($user);
            $billingAddress->setDefaultBillingAddress(true);
        }

        $shippingAddress = $this->getDefaultShippingAddress($user);
        if (!$shippingAddress instanceof Address) {
            $billingAddress->setDefaultShippingAddress(true);
        }

        $this->entityManager->persist($billingAddress);
        $this->entityManager->flush();

        $io->success('Address updated successfully');

        return Command::SUCCESS;
    }

    private function getUser(string $email): ?User
    {
        return $this->entityManager->getRepository(User::class)->findOneBy(['email' => $email]);
    }

    private function getDefaultBillingAddress(User $user): ?Address
    {
        return $this->entityManager->getRepository(Address::class)->findDefaultBillingAddress($user);
    }

    private function getDefaultShippingAddress(User $user): ?Address
    {
        return $this->entityManager->getRepository(Address::class)->findDefaultShippingAddress($user);
    }

    private function createBillingAddress(User $user): Address
    {
        return AddressFactory::createOne([
            'customer' => $user,
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
        ])->_real();
    }
}
