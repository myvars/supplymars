<?php

namespace App\Command;

use App\Entity\CustomerOrder;
use App\Service\Order\ProcessOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;

#[AsCommand(
    name: 'app:process-customer-order',
    description: 'Build POs for customer order',
)]
class processCustomerOrderCommand extends Command
{
    public const DEFAULT_USER_EMAIL = 'adam@admin.com';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface  $tokenStorage,
        private readonly UserProviderInterface  $userProvider,
        private readonly ProcessOrder           $orderProcessor,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('orderCount', InputArgument::REQUIRED, 'Order count to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $orderCount = $input->getArgument('orderCount');

        $customerOrders = $this->getNextCustomerOrders($orderCount);

        if (!$customerOrders) {
            $io->success('No customer orders to process');

            return Command::SUCCESS;
        }

        $this->setDefaultUser();

        $processedOrders = 0;
        foreach ($customerOrders as $customerOrder) {
            $this->orderProcessor->processOrder($customerOrder);
            $processedOrders++;

            $io->note(sprintf('Customer order %05d processed', $customerOrder->getId()));
        }

        $io->success(sprintf('%d customer orders processed', $processedOrders));

        return Command::SUCCESS;
    }

    private function getNextCustomerOrders(int $orderCount): ?array
    {
        return $this->entityManager->getRepository(CustomerOrder::class)->findNextOrdersToBeProcessed($orderCount);
    }

    public function setDefaultUser(): void
    {
        $user = $this->userProvider->loadUserByIdentifier(self::DEFAULT_USER_EMAIL);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }
}
