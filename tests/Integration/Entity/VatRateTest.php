<?php

namespace App\Tests\Integration\Entity;

use App\Entity\VatRate;
use App\Factory\VatRateFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class VatRateTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $this->validator = static::getContainer()->get('validator');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateReadUpdateDeleteVatRate(): void
    {
        $vatRate = new VatRate();
        $vatRate
            ->setName('Test VatRate')
            ->setRate(0.21);

        $this->entityManager->persist($vatRate);
        $this->entityManager->flush();

        $this->assertNotNull($vatRate->getId());

        $vatRate->setName('Updated VatRate');
        $this->entityManager->flush();

        $this->assertEquals('Updated VatRate', $vatRate->getName());

        $this->entityManager->remove($vatRate);
        $this->entityManager->flush();

        $this->assertNull($vatRate->getId());
    }

    public function testVatRateFindBySearch(): void
    {
        VatRateFactory::createOne(['name' => 'Test VatRate A']);
        VatRateFactory::createOne(['name' => 'Test VatRate B']);

        $result = $this->entityManager->getRepository(VatRate::class)->findBySearch('Test VatRate', 1);
        $this->assertCount(1, $result);
        $this->assertEquals('Test VatRate A', $result[0]->getName());
    }

    public function testVatRateFindBySearchQueryBuilder(): void
    {
        VatRateFactory::createOne(['name' => 'Test VatRate A']);
        VatRateFactory::createOne(['name' => 'Test VatRate B']);

        $vatRates = $this->entityManager
            ->getRepository(VatRate::class)
            ->findBySearchQueryBuilder('Test VatRate', 'name', 'asc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $vatRates);
        $this->assertEquals('Test VatRate A', $vatRates[0]->getName());
        $this->assertEquals('Test VatRate B', $vatRates[1]->getName());

        $vatRates = $this->entityManager
            ->getRepository(VatRate::class)
            ->findBySearchQueryBuilder('Test VatRate', 'name', 'desc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $vatRates);
        $this->assertEquals('Test VatRate B', $vatRates[0]->getName());
        $this->assertEquals('Test VatRate A', $vatRates[1]->getName());
    }

    /**
     * @dataProvider getValidationTestCases
     */
    public function testVatRateValidation(
        $name,
        $rate,
        $expected
    ): void {
        $vatRate = new VatRate();
        $vatRate
            ->setName($name)
            ->setRate($rate);

        $result = $this->validator->validate($vatRate);
        $this->assertEquals($expected, count($result) === 0);
    }

    public function getValidationTestCases(): array
    {
        return [
            'Succeeds when data is correct' => ['A new vatRate', 0.21, true],
            'Fails when name is missing' => ['', 0.21, false],
            'Fails when rate is missing' => ['A new vatRate', null, false],
            'Fails when rate is less than 0' => ['A new vatRate', -0.21, false],
        ];
    }
}