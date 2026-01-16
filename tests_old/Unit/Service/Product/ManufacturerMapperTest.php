<?php

namespace App\Tests\Unit\Service\Product;

use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Infrastructure\Persistence\Doctrine\ManufacturerDoctrineRepository;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Service\Product\ManufacturerMapper;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Validator\ConstraintViolationListInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ManufacturerMapperTest extends TestCase
{
    private MockObject $em;

    private MockObject $validator;

    private ManufacturerMapper $manufacturerMapper;

    protected function setUp(): void
    {
        $this->em = $this->createMock(EntityManagerInterface::class);
        $this->validator = $this->createMock(ValidatorInterface::class);
        $this->manufacturerMapper = new ManufacturerMapper($this->em, $this->validator);
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

        $this->em->method('getRepository')->willReturnMap([
            [Manufacturer::class, $this->createMock(ManufacturerDoctrineRepository::class)],
        ]);
        $this->em->getRepository(Manufacturer::class)->method('findOneBy')->willReturn($manufacturer);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->once())->method('persist');
        $this->em->expects($this->once())->method('flush');

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

        $this->em->method('getRepository')->willReturnMap([
            [Manufacturer::class, $this->createMock(ManufacturerDoctrineRepository::class)],
        ]);
        $this->em->getRepository(Manufacturer::class)->method('findOneBy')->willReturn(null);

        $this->validator->method('validate')->willReturn($this->createMock(ConstraintViolationListInterface::class));

        $this->em->expects($this->exactly(2))->method('persist');
        $this->em->expects($this->exactly(2))->method('flush');

        $createdManufacturer = $this->manufacturerMapper->createManufacturerFromSupplierProduct($supplierProduct);

        $this->assertInstanceOf(Manufacturer::class, $createdManufacturer);
        $this->assertSame('Sony', $createdManufacturer->getName());
    }
}
