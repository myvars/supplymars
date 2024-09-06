<?php

namespace App\Tests\Integration\Entity;

use App\Entity\SupplierProduct;
use App\Factory\ProductFactory;
use App\Factory\SupplierFactory;
use App\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class SupplierProductTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->validator = static::getContainer()->get('validator');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateReadUpdateDeleteSupplierProduct(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();
        $supplier = SupplierFactory::createOne(['name' => 'Test Supplier'])->_real();

        $supplierProduct = new SupplierProduct();
        $supplierProduct
            ->setName('Test SupplierProduct')
            ->setSupplier($supplier)
            ->setProductCode('Test ProductCode')
            ->setWeight(1)
            ->setCost(100)
            ->setStock(1)
            ->setMfrPartNumber('Test MfrPartNumber')
            ->setLeadTimeDays(1)
            ->setProduct($product)
            ->setIsActive(true);

        $this->entityManager->persist($supplierProduct);
        $this->entityManager->flush();

        $this->assertNotNull($supplierProduct->getId());

        $supplierProduct->setName('Updated SupplierProduct');
        $this->entityManager->flush();

        $this->assertEquals('Updated SupplierProduct', $supplierProduct->getName());

        $this->entityManager->remove($supplierProduct);
        $this->entityManager->flush();

        $this->assertNull($supplierProduct->getId());
    }

    public function testSupplierProductSupplierIsMissing(): void
    {
        $product = ProductFactory::createOne(['name' => 'Test Product'])->_real();

        $supplierProduct = new SupplierProduct();
        $supplierProduct
            ->setName('Test SupplierProduct')
            ->setProductCode('Test ProductCode')
            ->setWeight(1)
            ->setCost(100)
            ->setStock(1)
            ->setMfrPartNumber('Test MfrPartNumber')
            ->setLeadTimeDays(1)
            ->setProduct($product)
            ->setIsActive(true);

        $result = $this->validator->validate($supplierProduct);
        $this->assertCount(1, $result);
        $this->assertEquals('Please enter a supplier', $result[0]->getMessage());
    }

    public function testSupplierProductFindBySearch(): void
    {
        SupplierProductFactory::createOne(['name' => 'Test Supplier Product A']);
        SupplierProductFactory::createOne(['name' => 'Test Supplier Product B']);

        $result = $this->entityManager->getRepository(SupplierProduct::class)->findBySearch('Test Supplier Product', 1);
        $this->assertCount(1, $result);
        $this->assertEquals('Test Supplier Product A', $result[0]->getName());
    }

    public function testSupplierProductFindBySearchQueryBuilder(): void
    {
        SupplierProductFactory::createOne(['name' => 'Test Supplier Product A']);
        SupplierProductFactory::createOne(['name' => 'Test Supplier Product B']);

        $supplierProducts = $this->entityManager
            ->getRepository(SupplierProduct::class)
            ->findBySearchQueryBuilder('Test Supplier Product', 'name', 'asc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $supplierProducts);
        $this->assertEquals('Test Supplier Product A', $supplierProducts[0]->getName());
        $this->assertEquals('Test Supplier Product B', $supplierProducts[1]->getName());

        $supplierProducts = $this->entityManager
            ->getRepository(SupplierProduct::class)
            ->findBySearchQueryBuilder('Test Supplier Product', 'name', 'desc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $supplierProducts);
        $this->assertEquals('Test Supplier Product B', $supplierProducts[0]->getName());
        $this->assertEquals('Test Supplier Product A', $supplierProducts[1]->getName());
    }

    /**
     * @dataProvider getValidationTestCases
     */
    public function testSupplierProductValidation(
        ?string $name,
        ?string $productCode,
        ?int $weight,
        ?string $cost,
        int $stock,
        ?string $mfrPartNumber,
        ?int $leadTimeDays,
        bool $isActive,
        bool $expected
    ): void {
        $supplier = SupplierFactory::createOne(['name' => 'Test Supplier'])->_real();

        $supplierProduct = new SupplierProduct();
        $supplierProduct
            ->setName($name)
            ->setSupplier($supplier)
            ->setProductCode($productCode)
            ->setWeight($weight)
            ->setCost($cost)
            ->setStock($stock)
            ->setMfrPartNumber($mfrPartNumber)
            ->setLeadTimeDays($leadTimeDays)
            ->setIsActive($isActive);

        $result = $this->validator->validate($supplierProduct);
        $this->assertEquals($expected, count($result) === 0);
    }

    public function getValidationTestCases(): array
    {
        return [
            'Succeeds when data is correct' => ['Test SupplierProduct', 'Test ProductCode', 1, '100', 1, 'Test MfrPartNumber', 1, true, true],
            'Fails when name is missing' => ['', 'Test ProductCode', 1, '100', 1, 'Test MfrPartNumber', 1, true, false],
            'Fails when productCode is missing' => ['Test SupplierProduct', '', 1, '100', 1, 'Test MfrPartNumber', 1, true, false],
            'Fails when weight is missing' => ['Test SupplierProduct', 'Test ProductCode', null, '100', 1, 'Test MfrPartNumber', 1, true, false],
            'Fails when weight is less than 0' => ['Test SupplierProduct', 'Test ProductCode', -1, '100', 1, 'Test MfrPartNumber', 1, true, false],
            'Fails when weight is greater than 100000' => ['Test SupplierProduct', 'Test ProductCode', 100001, '100', 1, 'Test MfrPartNumber', 1, true, false],
            'Fails when cost is missing' => ['Test SupplierProduct', 'Test ProductCode', 1, '', 1, 'Test MfrPartNumber', 1, true, false],
            'Fails when cost is less than 0' => ['Test SupplierProduct', 'Test ProductCode', 1, '-1', 1, 'Test MfrPartNumber', 1, true, false],
            'Fails when stock is less than 0' => ['Test SupplierProduct', 'Test ProductCode', 1, '100', -1, 'Test MfrPartNumber', 1, true, false],
            'Fails when stock is greater than 10000' => ['Test SupplierProduct', 'Test ProductCode', 1, '100', 10001, 'Test MfrPartNumber', 1, true, false],
            'Fails when mfrPartNumber is missing' => ['Test SupplierProduct', 'Test ProductCode', 1, '100', 1, '', 1, true, false],
            'Fails when LeadTimeDays is missing' => ['Test SupplierProduct', 'Test ProductCode', 1, '100', 1, 'Test MfrPartNumber', null, true, false],
            'Fails when leadTimeDays is less than 0' => ['Test SupplierProduct', 'Test ProductCode', 1, '100', 1, 'Test MfrPartNumber', -1, true, false],
            'Fails when leadTimeDays is greater than 1000' => ['Test SupplierProduct', 'Test ProductCode', 1, '100', 1, 'Test MfrPartNumber', 1001, true, false],
        ];
    }
}