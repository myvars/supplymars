<?php

namespace App\Tests\Unit\Service\OrderProcessing;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use App\Entity\User;
use App\Enum\PurchaseOrderStatus;
use App\Repository\SupplierRepository;
use App\Service\OrderProcessing\SupplierUtility;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SupplierUtilityTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $tokenStorage;

    private MockObject $userProvider;

    private MockObject $changeStatusService;

    private SupplierUtility $supplierUtility;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->tokenStorage = $this->createMock(TokenStorageInterface::class);
        $this->userProvider = $this->createMock(UserProviderInterface::class);
        $this->changeStatusService = $this->createMock(ChangePurchaseOrderItemStatus::class);
        $this->supplierUtility = new SupplierUtility(
            $this->entityManager,
            $this->tokenStorage,
            $this->userProvider,
            $this->changeStatusService
        );
    }

    public function testGetRandomSupplier(): void
    {
        $supplier = $this->createMock(Supplier::class);
        $this->entityManager->method('getRepository')->willReturn($this->createMock(SupplierRepository::class));
        $this->entityManager->getRepository(Supplier::class)->method('findAll')->willReturn([$supplier]);

        $result = $this->supplierUtility->getRandomSupplier();
        $this->assertInstanceOf(Supplier::class, $result);
    }

    public function testChangePurchaseOrderItemStatus(): void
    {
        $purchaseOrderItem = $this->createMock(PurchaseOrderItem::class);
        $purchaseOrderItem->method('getStatus')->willReturn(PurchaseOrderStatus::PENDING);
        $purchaseOrderItem->method('getId')->willReturn(1);

        $this->changeStatusService->expects($this->once())->method('fromDto');

        $this->supplierUtility->changePurchaseOrderItemStatus(
            $purchaseOrderItem,
            PurchaseOrderStatus::PENDING,
            PurchaseOrderStatus::PROCESSING
        );
    }

    public function testSetDefaultUser(): void
    {
        $user = $this->createMock(User::class);
        $this->userProvider->method('loadUserByIdentifier')->willReturn($user);

        $this->tokenStorage->expects($this->once())->method('setToken')->with($this->isInstanceOf(UsernamePasswordToken::class));

        $this->supplierUtility->setDefaultUser();
    }
}