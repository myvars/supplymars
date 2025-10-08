<?php

namespace App\Command\OrderProcessing;

use App\DTO\CreateOrderDto;
use App\Entity\Address;
use App\Entity\CustomerOrder;
use App\Entity\CustomerOrderItem;
use App\Entity\Product;
use App\Entity\User;
use App\Enum\ShippingMethod;
use App\Service\Order\CreateOrder;
use App\Service\OrderProcessing\RandomAddressFactory;
use App\Service\OrderProcessing\RandomUserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:create-customer-orders',
    description: 'Create new customer orders',
)]
class createCustomerOrdersCommand
{
    public const int MAX_ORDER_LINES = 5;

    public const int MAX_LINE_QTY = 5;

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly CreateOrder $createOrder,
        private readonly RandomUserFactory $randomUserFactory,
        private readonly RandomAddressFactory $randomAddressFactory,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Order count')] string $orderCount,
        #[Option(description: 'Randomise')] bool $random = false,
    ): int {
        $io = new SymfonyStyle($input, $output);
        if ($orderCount < 1) {
            $io->error('Order count must be greater than 0');

            return Command::FAILURE;
        }

        if ($random) {
            $orderCount = random_int(0, $orderCount);
        }

        $ordersCreated = 0;
        for ($i = 0; $i < $orderCount; ++$i) {
            // sleep to simulate real world
            sleep(random_int(1, intdiv(300, $orderCount)));
            $this->createOrder();
            ++$ordersCreated;

            $this->entityManager->clear();
        }

        $io->success(sprintf('Created %d customer orders', $ordersCreated));

        return Command::SUCCESS;
    }

    public function createOrder(): void
    {
        $user = $this->getUser();
        $customerOrder = $this->placeCustomerOrder($user);
        $this->addCustomerOrderItems($customerOrder);
    }

    private function getUser(): User
    {
        if (0 === random_int(0, 2)) {
            $user = $this->entityManager->getRepository(User::class)->getRandomUser();
        } else {
            $user = $this->createUser();
        }

        if (!$user->getBillingAddress() instanceof Address) {
            $this->createBillingAddress($user);
        }

        return $user;
    }

    private function createUser(): User
    {
        $user = $this->randomUserFactory->create();
        $this->entityManager->persist($user);
        $this->entityManager->flush();

        return $user;
    }

    private function createBillingAddress(User $user): void
    {
        $address = $this->randomAddressFactory->create($user);
        $address->setIsDefaultShippingAddress(true);
        $address->setIsDefaultBillingAddress(true);

        $user->addAddress($address);
        $this->entityManager->persist($address);
        $this->entityManager->flush();
    }

    private function placeCustomerOrder(User $user): CustomerOrder
    {
        $createOrderDto = new CreateOrderDto();
        $createOrderDto->setCustomerId($user->getId());
        $createOrderDto->setCustomerOrderRef('TEST-'.sprintf('%04d', $user->getId()));

        $shippingMethods = ShippingMethod::cases();
        $createOrderDto->setShippingMethod($shippingMethods[array_rand($shippingMethods)]);

        return $this->createOrder->fromDto($createOrderDto);
    }

    private function addCustomerOrderItems(CustomerOrder $customerOrder): void
    {
        $products = $this->getRandomProducts(random_int(1, self::MAX_ORDER_LINES));

        foreach ($products as $product) {
            $customerOrderItem = CustomerOrderItem::createFromProduct(
                $customerOrder,
                $product,
                random_int(1, self::MAX_LINE_QTY)
            );

            $this->entityManager->persist($customerOrderItem);
        }

        $this->entityManager->flush();
    }

    private function getRandomProducts(int $productCount): array
    {
        return $this->entityManager->getRepository(Product::class)->findRandomProducts($productCount);
    }
}
