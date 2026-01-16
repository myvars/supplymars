<?php

namespace App\Tests\Purchasing\Integration;

use App\Purchasing\Application\Search\PurchaseOrderSearchCriteria;
use App\Purchasing\Domain\Model\PurchaseOrder\PurchaseOrderStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class PurchaseOrderSearchCriteriaIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidPurchaseOrderSearchDto(): void
    {
        $criteria = new PurchaseOrderSearchCriteria();
        $criteria->setSort('createdAt');
        $criteria->setSortDirection('ASC');
        $criteria->purchaseOrderId = 123;
        $criteria->orderId = 456;
        $criteria->customerId = 789;
        $criteria->productId = 101;
        $criteria->supplierId = 202;
        $criteria->startDate = '2023-01-01';
        $criteria->endDate = '2023-12-31';
        $criteria->purchaseOrderStatus = PurchaseOrderStatus::PROCESSING->value;

        $errors = $this->validator->validate($criteria);
        $this->assertCount(0, $errors);
    }

    public function testInvalidPurchaseOrderId(): void
    {
        $criteria = new PurchaseOrderSearchCriteria();
        $criteria->purchaseOrderId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Purchase Order Id', $errors[0]->getMessage());
    }

    public function testInvalidCustomerOrderId(): void
    {
        $criteria = new PurchaseOrderSearchCriteria();
        $criteria->orderId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Customer Order Id', $errors[0]->getMessage());
    }

    public function testInvalidCustomerId(): void
    {
        $criteria = new PurchaseOrderSearchCriteria();
        $criteria->customerId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Customer Id', $errors[0]->getMessage());
    }

    public function testInvalidProductId(): void
    {
        $criteria = new PurchaseOrderSearchCriteria();
        $criteria->productId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Product Id', $errors[0]->getMessage());
    }

    public function testInvalidSupplierId(): void
    {
        $criteria = new PurchaseOrderSearchCriteria();
        $criteria->supplierId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Supplier Id', $errors[0]->getMessage());
    }

    public function testInvalidStartDate(): void
    {
        $criteria = new PurchaseOrderSearchCriteria();
        $criteria->startDate = 'invalid-date';

        $errors = $this->validator->validate($criteria);
        $this->assertSame('This value is not a valid date.', $errors[0]->getMessage());
    }

    public function testInvalidEndDate(): void
    {
        $criteria = new PurchaseOrderSearchCriteria();
        $criteria->endDate = 'invalid-date';

        $errors = $this->validator->validate($criteria);
        $this->assertSame('This value is not a valid date.', $errors[0]->getMessage());
    }
}
