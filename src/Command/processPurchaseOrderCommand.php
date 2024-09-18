<?php

namespace App\Command;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\PurchaseOrder;
use App\Entity\Supplier;
use App\Enum\PurchaseOrderStatus;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
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
    name: 'app:process-purchase-order',
    description: 'process purchase orders',
)]
class processPurchaseOrderCommand extends Command
{
    public const DEFAULT_USER_EMAIL = 'adam@admin.com';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface  $tokenStorage,
        private readonly UserProviderInterface  $userProvider,
        private readonly ChangePurchaseOrderItemStatus $changePurchaseOrderItemStatus,
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('poCount', InputArgument::REQUIRED, 'PO count to process');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $poCount = $input->getArgument('poCount');

        $supplier = $this->getRandomSupplier();

        if (!$supplier) {
            $io->error('No supplier found');

            return Command::FAILURE;
        }

        $io->success(sprintf('Processing purchase orders for supplier %s', $supplier->getName()));

        $purchaseOrders = $this->getWaitingPurchaseOrders($supplier, $poCount);

        if (!$purchaseOrders) {
            $io->success('No purchase orders to process');

            return Command::SUCCESS;
        }

        $this->setDefaultUser();

        $processedPoCount = 0;
        foreach ($purchaseOrders as $purchaseOrder) {
            $newStatus = random_int(1,25) !== 1 ? PurchaseOrderStatus::ACCEPTED : PurchaseOrderStatus::REJECTED;
            $this->processPurchaseOrderItems($purchaseOrder, $newStatus);
            $processedPoCount++;

            $io->note(sprintf('Purchase order %05d : %s', $purchaseOrder->getId(), $newStatus->value));
        }

        $io->success(sprintf('Processed %d purchase orders', $processedPoCount));

        return Command::SUCCESS;
    }

    public function setDefaultUser(): void
    {
        $user = $this->userProvider->loadUserByIdentifier(self::DEFAULT_USER_EMAIL);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }

    private function getWaitingPurchaseOrders(Supplier $supplier, int $poCount): ?array
    {
        return $this->entityManager->getRepository(PurchaseOrder::class)
            ->findWaitingPurchaseOrders($supplier, $poCount);
    }

    private function processPurchaseOrderItems(PurchaseOrder $purchaseOrder, PurchaseOrderStatus $newStatus): void
    {
        foreach ($purchaseOrder->getPurchaseOrderItems() as $purchaseOrderItem) {
            if ($purchaseOrderItem->getStatus() !== PurchaseOrderStatus::PROCESSING) {

                continue;
            }
            $changePurchaseOrderItemStatusDto = new ChangePurchaseOrderItemStatusDto(
                $purchaseOrderItem->getId(),
                $newStatus
            );
            $this->changePurchaseOrderItemStatus->fromDto($changePurchaseOrderItemStatusDto);
        }
    }

    private function getRandomSupplier()
    {
        $suppliers = $this->entityManager->getRepository(Supplier::class)->findAll();
        return $suppliers[array_rand($suppliers)];
    }
}
