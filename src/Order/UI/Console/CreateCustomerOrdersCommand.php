<?php

namespace App\Order\UI\Console;

use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Customer\Domain\Model\Address\Address;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\AddressRepository;
use App\Customer\Domain\Repository\UserRepository;
use App\Customer\Infrastructure\Factory\RandomAddressFactory;
use App\Customer\Infrastructure\Factory\RandomUserFactory;
use App\Order\Application\Command\CreateOrder;
use App\Order\Application\Command\CreateOrderItem;
use App\Order\Application\Handler\CreateOrderHandler;
use App\Order\Application\Handler\CreateOrderItemHandler;
use App\Order\Domain\Model\Order\CustomerOrder;
use App\Order\Domain\Model\Order\OrderPublicId;
use App\Order\Domain\Repository\OrderRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\ValueObject\ShippingMethod;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
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
readonly class CreateCustomerOrdersCommand
{
    public const int MAX_ORDER_LINES = 5;

    public const int MAX_LINE_QTY = 5;

    public function __construct(
        private ProductRepository $products,
        private OrderRepository $orders,
        private UserRepository $customers,
        private AddressRepository $addresses,
        private RandomUserFactory $randomUserFactory,
        private RandomAddressFactory $randomAddressFactory,
        private CreateOrderHandler $createOrderHandler,
        private CreateOrderItemHandler $createOrderItemHandler,
        private DefaultUserAuthenticator $defaultUserAuthenticator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Order count')]
        int $orderCount = 0,
        #[Option(description: 'Randomise')]
        bool $random = false,
        #[Option(description: 'Run without persisting changes')]
        bool $dryRun = false,
        #[Option(description: 'Skip timing delays (for testing)')]
        bool $skipTiming = false,
    ): int {
        $io = new SymfonyStyle($input, $output);
        if ($orderCount < 1) {
            $io->error('Order count must be > 0');

            return Command::INVALID;
        }

        if ($random) {
            $orderCount = random_int(0, $orderCount);
            if ($orderCount === 0) {
                $io->note('No orders to create (randomised to 0)');

                return Command::SUCCESS;
            }
        }

        $this->defaultUserAuthenticator->ensureAuthenticated();

        $io->section(sprintf(
            '%sCreating %d new orders',
            $dryRun ? '[DRY RUN] ' : '',
            $orderCount
        ));
        $progress = $io->createProgressBar($orderCount);
        $progress->start();

        $processed = 0;
        $processedIds = [];

        for ($i = 0; $i < $orderCount; ++$i) {
            if (!$skipTiming) {
                sleep(random_int(1, intdiv(300, $orderCount)));
            }

            if ($dryRun) {
                $processedIds[] = 'DRY-RUN-' . ($i + 1);
                ++$processed;
                $progress->advance();
                continue;
            }

            $user = $this->getOrCreateUser();
            $this->createBillingAddress($user);

            $order = $this->placeCustomerOrder($user);
            $this->addCustomerOrderItems($order);

            $processedIds[] = (string) $order->getId();
            ++$processed;
            $progress->advance();
        }

        $progress->finish();
        $io->newLine(2);
        $io->success(sprintf(
            '%sCreated %d customer orders.',
            $dryRun ? '[DRY RUN] ' : '',
            $processed
        ));

        if ($output->isVerbose()) {
            $io->section('Created Order IDs');
            $io->listing($processedIds);
        }

        return Command::SUCCESS;
    }

    private function getOrCreateUser(): User
    {
        if (0 === random_int(0, 2)) {
            return $this->customers->getRandomUser();
        }

        $user = $this->randomUserFactory->create();
        $this->customers->add($user);

        $this->flusher->flush();

        return $user;
    }

    private function createBillingAddress(User $user): void
    {
        $address = $user->getBillingAddress();
        if ($address instanceof Address) {
            return;
        }

        $address = $this->randomAddressFactory->create($user, isShipping: true, isBilling: true);
        $this->addresses->add($address);
        $user->addAddress($address);

        $this->flusher->flush();
    }

    private function placeCustomerOrder(User $user): CustomerOrder
    {
        $shippingMethods = ShippingMethod::cases();
        $result = ($this->createOrderHandler)(
            new CreateOrder(
                $user->getId(),
                $shippingMethods[array_rand($shippingMethods)],
                'TEST-' . sprintf('%04d', $user->getId()),
            )
        );

        if (!$result->payload instanceof OrderPublicId) {
            throw new \RuntimeException('Failed to create customer order');
        }

        return $this->getCustomerOrder($result->payload);
    }

    private function addCustomerOrderItems(CustomerOrder $order): void
    {
        $products = $this->getRandomProducts(random_int(1, self::MAX_ORDER_LINES));
        if ($products === []) {
            return;
        }

        foreach ($products as $product) {
            if (!$product instanceof Product) {
                continue;
            }

            ($this->createOrderItemHandler)(
                new CreateOrderItem(
                    $order->getPublicId(),
                    $product->getId(),
                    random_int(1, self::MAX_LINE_QTY)
                )
            );
        }
    }

    /**
     * @return array<int, Product>
     */
    private function getRandomProducts(int $productCount): array
    {
        return $this->products->findRandomProducts($productCount);
    }

    private function getCustomerOrder(OrderPublicId $id): CustomerOrder
    {
        return $this->orders->getByPublicId($id);
    }
}
