<?php

namespace App\Tests\Integration\Service\PurchaseOrder;

use App\Entity\PurchaseOrder;
use App\Factory\CustomerOrderFactory;
use App\Factory\SupplierFactory;
use App\Service\PurchaseOrder\CreatePurchaseOrder;
use App\Service\Utility\DomainEventDispatcher;
use App\Story\StaffUserStory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CreatePurchaseOrderIntegrationTest extends KernelTestCase
{
    use Factories;

    private CreatePurchaseOrder $createPurchaseOrder;

    protected function setUp(): void
    {
        self::bootKernel();
        $entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $domainEventDispatcher = static::getContainer()->get(DomainEventDispatcher::class);
        $this->createPurchaseOrder = new CreatePurchaseOrder($entityManager, $validator, $domainEventDispatcher);
        StaffUserStory::load();
    }

    public function testCreatePurchaseOrderSuccessfully(): void
    {
        $supplier = SupplierFactory::createOne()->_real();
        $customerOrder = CustomerOrderFactory::createOne()->_real();

        $purchaseOrder = $this->createPurchaseOrder->fromOrder($customerOrder, $supplier);

        $this->assertInstanceOf(PurchaseOrder::class, $purchaseOrder);
        $this->assertSame($customerOrder, $purchaseOrder->getCustomerOrder());
        $this->assertSame($supplier, $purchaseOrder->getSupplier());
    }
}