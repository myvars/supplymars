<?php

namespace App\Command;

use App\DTO\CreateOrderDto;
use App\Entity\Address;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\User;
use App\Enum\ShippingMethod;
use App\Factory\AddressFactory;
use App\Factory\ProductFactory;
use App\Factory\UserFactory;
use App\Service\Order\CreateOrder;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Input\InputOption;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-customer-orders',
    description: 'Create new customer orders',
)]
class createCustomerOrdersCommand extends Command
{
    public const MAX_ORDER_LINES = 5;
    public const MAX_LINE_QTY = 5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CreateOrder $createOrder
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('orderCount', InputArgument::REQUIRED, 'Order count');
        $this->addOption('random', null, InputOption::VALUE_NONE, 'Random order count');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $orderCount = $input->getArgument('orderCount');
        if ($orderCount < 1) {
            $io->error('Order count must be greater than 0');

            return Command::FAILURE;
        }

        if ($input->getOption('random')) {
            $orderCount = random_int(0, $orderCount);
        }

        $ordersCreated = 0;
        for ($i = 0; $i < $orderCount; $i++) {
            $this->createOrder();
            $ordersCreated++;
        }

        $io->success(sprintf('Created %d customer orders', $ordersCreated));

        return Command::SUCCESS;
    }

    public function createOrder(): void
    {
        $user = $this->getUser();
        $billingAddress = $this->createBillingAddress($user);
        $customerOrder = $this->placeCustomerOrder($user);
        $this->addCustomerOrderItems($customerOrder);
    }

    private function getUser(): User
    {
        if (random_int(0, 2) === 0) {
            return UserFactory::random()->_real();
        }

        return UserFactory::createOne(['isVerified' => true])->_real();
    }

    private function createUser(): User
    {
        return UserFactory::createOne(['isVerified' => true])->_real();
    }

    private function createBillingAddress(User $user): Address
    {
        return AddressFactory::createOne([
            'customer' => $user,
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'defaultBillingAddress' => true,
            'defaultShippingAddress' => true,
        ])->_real();
    }

    private function placeCustomerOrder(User $user): CustomerOrder
    {
        $createOrderDto = new CreateOrderDto();
        $createOrderDto->setCustomerId($user->getId());
        $createOrderDto->setCustomerOrderRef('TEST-' . sprintf('%04d', $user->getId()));
        $shippingMethods = ShippingMethod::cases();
        $createOrderDto->setShippingMethod($shippingMethods[array_rand($shippingMethods)]);

        return $this->createOrder->fromDto($createOrderDto);
    }

    private function addCustomerOrderItems(CustomerOrder $customerOrder): void
    {
        $products = ProductFactory::randomSet(random_int(1, self::MAX_ORDER_LINES));

        foreach ($products as $product) {
            $customerOrderItem = new CustomerOrderItem();
            $customerOrderItem->createFromProduct($product->_real(), random_int(1,self::MAX_LINE_QTY));
            $customerOrder->addCustomerOrderItem($customerOrderItem);
            $this->entityManager->persist($customerOrderItem);
        }
        $this->entityManager->flush();
    }
}
