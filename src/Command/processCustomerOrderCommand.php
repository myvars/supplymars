<?php

namespace App\Command;

use App\DTO\CreateOrderDto;
use App\Entity\Address;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use App\Entity\SupplierProduct;
use App\Entity\User;
use App\Enum\OrderStatus;
use App\Enum\ShippingMethod;
use App\Factory\AddressFactory;
use App\Factory\ProductFactory;
use App\Factory\UserFactory;
use App\Service\Order\CreateOrder;
use App\Service\PurchaseOrder\CreatePurchaseOrderItem;
use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:process-customer-order',
    description: 'Build POs for customer order',
)]
class processCustomerOrderCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CreatePurchaseOrderItem $createPurchaseOrderItem
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

        foreach ($customerOrders as $customerOrder) {
            $this->processOrder($customerOrder);
            $io->success(sprintf('Customer order %05d processed', $customerOrder->getId()));
        }

        return Command::SUCCESS;
    }

    private function getNextCustomerOrders(int $orderCount): ?array
    {
        return $this->entityManager->getRepository(CustomerOrder::class)->findNextOrdersToBeProcessed($orderCount);
    }

    private function processOrder(CustomerOrder $customerOrder): void
    {
        foreach($customerOrder->getCustomerOrderItems() as $customerOrderItem) {
            $lowestCostSupplier = $this->getLowestCostSupplierProduct(
                $customerOrderItem->getProduct(),
                $customerOrderItem->getQuantity()
            );

            if ($lowestCostSupplier instanceof SupplierProduct) {
                $this->createPurchaseOrderItem->fromOrder($customerOrderItem, $lowestCostSupplier);
            }
        }
    }

    private function getLowestCostSupplierProduct(Product $product, int $orderItemQty): ?SupplierProduct
    {
        $lowestCostSupplier = null;
        foreach($product->getActiveSupplierProducts() as $supplierProduct) {
            if ($supplierProduct->getStock() >= $orderItemQty) {
                //set lowest cost supplier
                if (!isset($lowestCostSupplier) || $supplierProduct->getCost() < $lowestCostSupplier->getCost()) {
                    $lowestCostSupplier = $supplierProduct;
                }
            }
        }

        return $lowestCostSupplier;
    }
}
