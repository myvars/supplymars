<?php

namespace App\Tests\Unit\Service\Product;

use PHPUnit\Framework\MockObject\MockObject;
use App\Entity\Manufacturer;
use App\Entity\SupplierManufacturer;
use App\Entity\SupplierProduct;
use App\Repository\ManufacturerRepository;
use App\Service\Product\ManufacturerMapper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ManufacturerMapperTest extends TestCase
{
    private MockObject $entityManager;

    private MockObject $validator;

    private ManufacturerMapper $manufacturerMapper;

    protected function setUp(): void
    {
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->manufacturerMapper = new ManufacturerMapper($this->entityManager, $this->validator);
    }

    public function testCreateManufacturerFromSupplierProductWithMissingSupplierManufacturer(): void
    {
        $this->expectException(\InvalidArgumentException::class);
        $this->expectExceptionMessage('Supplier manufacturer is missing');

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getSupplierManufacturer')->willReturn(null);

        $this->manufacturerMapper->createManufacturerFromSupplierProduct($supplierProduct);
    }

    public function testCreateManufacturerWhenManufacturerExists(): void
    {
        $supplierManufacturer = $this->createMock(SupplierManufacturer::class);
        $supplierManufacturer->method('getName')->willReturn('Sony');

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getSupplierManufacturer')->willReturn($supplierManufacturer);

        $manufacturer = $this->createMock(Manufacturer::class);
        $manufacturer->method('getName')->willReturn('Sony');
        $manufacturer->expects($this->once())->method('addSupplierManufacturer')->with($supplierManufacturer);

        $this->entityManager->method('getRepository')->willReturnMap([
            [Manufacturer::class, $this->createMock(ManufacturerRepository::class)]
        ]);
        $this->entityManager->getRepository(Manufacturer::class)->method('findOneBy')->willReturn($manufacturer);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->once())->method('persist');
        $this->entityManager->expects($this->once())->method('flush');

        $createdManufacturer = $this->manufacturerMapper->createManufacturerFromSupplierProduct($supplierProduct);

        $this->assertInstanceOf(Manufacturer::class, $createdManufacturer);
        $this->assertSame('Sony', $createdManufacturer->getName());
    }

    public function testCreateManufacturerFromSupplierProductSuccessfully(): void
    {
        $supplierManufacturer = $this->createMock(SupplierManufacturer::class);
        $supplierManufacturer->method('getName')->willReturn('Sony');

        $supplierProduct = $this->createMock(SupplierProduct::class);
        $supplierProduct->method('getSupplierManufacturer')->willReturn($supplierManufacturer);

        $this->entityManager->method('getRepository')->willReturnMap([
            [Manufacturer::class, $this->createMock(ManufacturerRepository::class)]
        ]);
        $this->entityManager->getRepository(Manufacturer::class)->method('findOneBy')->willReturn(null);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->entityManager->expects($this->exactly(2))->method('persist');
        $this->entityManager->expects($this->exactly(2))->method('flush');

        $createdManufacturer = $this->manufacturerMapper->createManufacturerFromSupplierProduct($supplierProduct);

        $this->assertInstanceOf(Manufacturer::class, $createdManufacturer);
        $this->assertSame('Sony', $createdManufacturer->getName());
    }
}