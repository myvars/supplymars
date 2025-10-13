<?php

namespace App\Tests\Integration\Service\OrderProcessing;

use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use App\Purchasing\Domain\Model\Supplier\Supplier;
use App\Service\OrderProcessing\SupplierUtility;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\PurchaseOrderItemFactory;
use tests\Shared\Factory\SupplierFactory;
use tests\Shared\Factory\UserFactory;
use Story\StaffUserStory;
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
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $tokenStorage = static::getContainer()->get(TokenStorageInterface::class);
        $userProvider = static::getContainer()->get(UserProviderInterface::class);
        $changeStatusService = static::getContainer()->get(ChangePurchaseOrderItemStatus::class);
        $this->supplierUtility = new SupplierUtility(
            $em,
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
        UserFactory::new()->asStaff()->create(['email' => 'adam@admin.com']);

        $this->supplierUtility->setDefaultUser();

        $token = static::getContainer()->get(TokenStorageInterface::class)->getToken();
        $this->assertNotNull($token);
        $this->assertSame('adam@admin.com', $token->getUser()->getUserIdentifier());
    }
}
