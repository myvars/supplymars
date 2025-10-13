<?php

namespace App\Tests\Integration\Service\PurchaseOrder;

use App\Service\PurchaseOrder\CreatePurchaseOrder;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\CustomerOrderFactory;
use tests\Shared\Factory\SupplierFactory;
use Story\StaffUserStory;
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
        $em = static::getContainer()->get(EntityManagerInterface::class);
        $validator = static::getContainer()->get(ValidatorInterface::class);
        $this->createPurchaseOrder = new CreatePurchaseOrder($em, $validator);
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
