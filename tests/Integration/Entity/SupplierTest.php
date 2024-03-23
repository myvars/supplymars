<?php

namespace App\Tests\Integration\Entity;

use App\Entity\Supplier;
use App\Factory\SupplierFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class SupplierTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $this->validator = static::getContainer()->get('validator');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateReadUpdateDeleteSupplier(): void
    {
        $supplier = new Supplier();
        $supplier
            ->setName('Test Supplier')
            ->setIsActive(true);

        $this->entityManager->persist($supplier);
        $this->entityManager->flush();

        $this->assertNotNull($supplier->getId());

        $supplier->setName('Updated Supplier');
        $this->entityManager->flush();

        $this->assertEquals('Updated Supplier', $supplier->getName());

        $this->entityManager->remove($supplier);
        $this->entityManager->flush();

        $this->assertNull($supplier->getId());
    }

    public function testSupplierFindBySearch(): void
    {
        SupplierFactory::createOne(['name' => 'Test Supplier A']);
        SupplierFactory::createOne(['name' => 'Test Supplier B']);

        $result = $this->entityManager->getRepository(Supplier::class)->findBySearch('Test Supplier', 1);
        $this->assertCount(1, $result);
        $this->assertEquals('Test Supplier A', $result[0]->getName());
    }

    public function testSupplierFindBySearchQueryBuilder(): void
    {
        SupplierFactory::createOne(['name' => 'Test Supplier A']);
        SupplierFactory::createOne(['name' => 'Test Supplier B']);

        $suppliers = $this->entityManager
            ->getRepository(Supplier::class)
            ->findBySearchQueryBuilder('Test Supplier', 'name', 'asc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $suppliers);
        $this->assertEquals('Test Supplier A', $suppliers[0]->getName());
        $this->assertEquals('Test Supplier B', $suppliers[1]->getName());

        $suppliers = $this->entityManager
            ->getRepository(Supplier::class)
            ->findBySearchQueryBuilder('Test Supplier', 'name', 'desc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $suppliers);
        $this->assertEquals('Test Supplier B', $suppliers[0]->getName());
        $this->assertEquals('Test Supplier A', $suppliers[1]->getName());
    }

    /**
     * @dataProvider getValidationTestCases
     */
    public function testSupplierValidation(
        $name,
        $isActive,
        $expected
    ): void {
        $supplier = new Supplier();
        $supplier
            ->setName($name)
            ->setIsActive($isActive);

        $result = $this->validator->validate($supplier);
        $this->assertEquals($expected, count($result) === 0);
    }

    public function getValidationTestCases(): array
    {
        return [
            'Succeeds when data is correct' => ['A new supplier', true, true],
            'Fails when name is missing' => ['', true, false],
        ];
    }
}