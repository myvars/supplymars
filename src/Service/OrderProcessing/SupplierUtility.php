<?php

namespace App\Service\OrderProcessing;

use App\DTO\ChangePurchaseOrderItemStatusDto;
use App\Entity\PurchaseOrderItem;
use App\Entity\Supplier;
use App\Enum\PurchaseOrderStatus;
use App\Service\PurchaseOrder\ChangePurchaseOrderItemStatus;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\Authentication\Token\UsernamePasswordToken;
use Symfony\Component\Security\Core\User\UserProviderInterface;

class SupplierUtility
{
    public const string DEFAULT_USER_EMAIL = 'adam@admin.com';

    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly TokenStorageInterface $tokenStorage,
        private readonly UserProviderInterface $userProvider,
        private readonly ChangePurchaseOrderItemStatus $changeStatusService,
    ) {
    }

    public function getRandomSupplier(): ?Supplier
    {
        $suppliers = $this->entityManager->getRepository(Supplier::class)->findAll();

        return $suppliers[array_rand($suppliers)] ?? null;
    }

    public function changePurchaseOrderItemStatus(
        PurchaseOrderItem $purchaseOrderItem,
        PurchaseOrderStatus $currentStatus,
        PurchaseOrderStatus $newStatus,
    ): void {
        if ($purchaseOrderItem->getStatus() !== $currentStatus) {
            return;
        }

        $dto = new ChangePurchaseOrderItemStatusDto($purchaseOrderItem->getId(), $newStatus);
        $this->changeStatusService->fromDto($dto);
    }

    public function setDefaultUser(): void
    {
        $user = $this->userProvider->loadUserByIdentifier(self::DEFAULT_USER_EMAIL);
        $token = new UsernamePasswordToken($user, 'main', $user->getRoles());
        $this->tokenStorage->setToken($token);
    }
}
