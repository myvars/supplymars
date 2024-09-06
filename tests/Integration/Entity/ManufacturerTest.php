<?php

namespace App\Tests\Integration\Entity;

use App\Entity\Manufacturer;
use App\Factory\ManufacturerFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ManufacturerTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->validator = static::getContainer()->get('validator');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateReadUpdateDeleteManufacturer(): void
    {
        $manufacturer = new Manufacturer();
        $manufacturer
            ->setName('Test Manufacturer')
            ->setIsActive(true);

        $this->entityManager->persist($manufacturer);
        $this->entityManager->flush();

        $this->assertNotNull($manufacturer->getId());

        $manufacturer->setName('Updated Manufacturer');
        $this->entityManager->flush();

        $this->assertEquals('Updated Manufacturer', $manufacturer->getName());

        $this->entityManager->remove($manufacturer);
        $this->entityManager->flush();

        $this->assertNull($manufacturer->getId());
    }

    public function testManufacturerFindBySearch(): void
    {
        ManufacturerFactory::createOne(['name' => 'Test Manufacturer A']);
        ManufacturerFactory::createOne(['name' => 'Test Manufacturer B']);

        $manufacturers = $this->entityManager->getRepository(Manufacturer::class)->findBySearch('Test Manufacturer', 1);
        $this->assertCount(1, $manufacturers);
    }

    public function testManufacturerFindBySearchQueryBuilder(): void
    {
        ManufacturerFactory::createOne(['name' => 'Test Manufacturer A']);
        ManufacturerFactory::createOne(['name' => 'Test Manufacturer B']);

        $manufacturers = $this->entityManager
            ->getRepository(Manufacturer::class)
            ->findBySearchQueryBuilder('Test Manufacturer', 'name', 'asc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $manufacturers);
        $this->assertEquals('Test Manufacturer A', $manufacturers[0]->getName());
        $this->assertEquals('Test Manufacturer B', $manufacturers[1]->getName());

        $manufacturers = $this->entityManager
            ->getRepository(Manufacturer::class)
            ->findBySearchQueryBuilder('Test Manufacturer', 'name', 'desc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $manufacturers);
        $this->assertEquals('Test Manufacturer B', $manufacturers[0]->getName());
        $this->assertEquals('Test Manufacturer A', $manufacturers[1]->getName());
    }

    /**
     * @dataProvider getValidationTestCases
     */
    public function testManufacturerValidation(
        string $name,
        bool $isActive,
        bool $expected
    ): void
    {
        $manufacturer = new Manufacturer();
        $manufacturer
            ->setName($name)
            ->setIsActive($isActive);

        $result = $this->validator->validate($manufacturer);
        $this->assertEquals($expected, count($result) === 0);
    }

    public function getValidationTestCases(): array
    {
        return [
            'Succeeds when data is correct' => ['A new manufacturer', true, true],
            'Fails when name is missing' => ['', true, false],
        ];
    }
}