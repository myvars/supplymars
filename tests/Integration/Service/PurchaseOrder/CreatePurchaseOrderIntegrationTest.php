<?php

namespace App\Tests\Integration\Service\PurchaseOrder;

use App\Factory\CustomerOrderFactory;
use App\Factory\SupplierFactory;
use App\Service\PurchaseOrder\CreatePurchaseOrder;
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
        $this->createPurchaseOrder = new CreatePurchaseOrder($entityManager, $validator);
        StaffUserStory::load();
    }

    public function testCreatePurchaseOrderSuccessfully(): void
    {
        $supplier = SupplierFactory::createOne();
        $customerOrder = CustomerOrderFactory::createOne();

        $purchaseOrder = $this->createPurchaseOrder->fromOrder($customerOrder, $supplier);

        $this->assertSame($customerOrder, $purchaseOrder->getCustomerOrder());
        $this->assertSame($supplier, $purchaseOrder->getSupplier());
    }
}
