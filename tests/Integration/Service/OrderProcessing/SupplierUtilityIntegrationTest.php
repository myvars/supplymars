<?php

namespace App\Tests\Integration\Service\OrderProcessing;

use App\Entity\Supplier;
use App\Enum\PurchaseOrderStatus;
use App\Factory\PurchaseOrderItemFactory;
use App\Factory\SupplierFactory;
use App\Factory\UserFactory;
use App\Service\OrderProcessing\SupplierUtility;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Zenstruck\Foundry\Test\Factories;

class SupplierUtilityIntegrationTest extends KernelTestCase
{
    use Factories;

    private SupplierUtility $supplierUtility;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $tokenStorage = static::getContainer()->get(TokenStorageInterface::class);
        $userProvider = static::getContainer()->get(UserProviderInterface::class);
        $changeStatusService = static::getContainer()->get(ChangePurchaseOrderItemStatus::class);
        $this->supplierUtility = new SupplierUtility(
            $entityManager,
            $tokenStorage,
            $userProvider,
            $changeStatusService
        );
        StaffUserStory::load();
    }

    public function testGetRandomSupplier(): void
    {
        SupplierFactory::createMany(3);
        $supplier = $this->supplierUtility->getRandomSupplier();
        $this->assertInstanceOf(Supplier::class, $supplier);
    }

    public function testChangePurchaseOrderItemStatus(): void
    {
        $purchaseOrderItem = PurchaseOrderItemFactory::createOne();
        $this->supplierUtility->changePurchaseOrderItemStatus(
            $purchaseOrderItem,
            PurchaseOrderStatus::PENDING,
            PurchaseOrderStatus::PROCESSING
        );

        $this->assertSame(PurchaseOrderStatus::PROCESSING, $purchaseOrderItem->getStatus());
    }

    public function testSetDefaultUser(): void
    {
        UserFactory::new()->staff()->create(['email' => 'adam@admin.com']);

        $this->supplierUtility->setDefaultUser();

        $token = static::getContainer()->get(TokenStorageInterface::class)->getToken();
        $this->assertNotNull($token);
        $this->assertSame('adam@admin.com', $token->getUser()->getUserIdentifier());
    }
}
