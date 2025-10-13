<?php

namespace App\Tests\Order\Integration;

use App\Order\Application\Search\OrderSearchCriteria;
use App\Order\Domain\Model\Order\OrderStatus;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class OrderSearchCriteriaIntegrationTest extends KernelTestCase
{
    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidOrderSearchDto(): void
    {
        $criteria = new OrderSearchCriteria();
        $criteria->setSort('createdAt');
        $criteria->setSortDirection('ASC');
        $criteria->orderId = 123;
        $criteria->purchaseOrderId = 456;
        $criteria->customerId = 789;
        $criteria->productId = 101;
        $criteria->startDate = '2023-01-01';
        $criteria->endDate = '2023-12-31';
        $criteria->orderStatus = OrderStatus::PENDING->value;

        $errors = $this->validator->validate($criteria);
        $this->assertCount(0, $errors);
    }

    public function testInvalidCustomerOrderId(): void
    {
        $criteria = new OrderSearchCriteria();
        $criteria->orderId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Customer Order Id', $errors[0]->getMessage());
    }

    public function testInvalidPurchaseOrderId(): void
    {
        $criteria = new OrderSearchCriteria();
        $criteria->purchaseOrderId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Purchase Order Id', $errors[0]->getMessage());
    }

    public function testInvalidCustomerId(): void
    {
        $criteria = new OrderSearchCriteria();
        $criteria->customerId = 0;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Customer Id', $errors[0]->getMessage());
    }

    public function testInvalidProductId(): void
    {
        $criteria = new OrderSearchCriteria();
        $criteria->productId = 0 ;

        $errors = $this->validator->validate($criteria);
        $this->assertSame('Please enter a valid Product Id', $errors[0]->getMessage());
    }

    public function testInvalidStartDate(): void
    {
        $criteria = new OrderSearchCriteria();
        $criteria->startDate = 'invalid-date';

        $errors = $this->validator->validate($criteria);
        $this->assertSame('This value is not a valid date.', $errors[0]->getMessage());
    }

    public function testInvalidEndDate(): void
    {
        $criteria = new OrderSearchCriteria();
        $criteria->endDate = 'invalid-date';

        $errors = $this->validator->validate($criteria);
        $this->assertSame('This value is not a valid date.', $errors[0]->getMessage());
    }
}
