<?php

namespace App\Service\Product;

use App\Entity\Manufacturer;
use App\Entity\SupplierManufacturer;
use App\Entity\SupplierProduct;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

class ManufacturerMapper
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager,
        private readonly ValidatorInterface $validator,
    ) {
    }

    public function createManufacturerFromSupplierProduct(SupplierProduct $supplierProduct): Manufacturer
    {
        $supplierManufacturer = $supplierProduct->getSupplierManufacturer();
        if (!$supplierManufacturer instanceof SupplierManufacturer) {
            throw new \InvalidArgumentException('Supplier manufacturer is missing');
        }

        $manufacturer = $this->manufacturerAlreadyExists($supplierManufacturer->getName());
        if ($manufacturer instanceof Manufacturer) {
            $this->mapManufacturerToSupplier($supplierManufacturer, $manufacturer);

            return $manufacturer;
        }

        $manufacturer = new Manufacturer()
            ->setName($supplierManufacturer->getName())
            ->setIsActive(true);

        $errors = $this->validator->validate($manufacturer);
        if (count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }

        $this->entityManager->persist($manufacturer);
        $this->entityManager->flush();

        $this->mapManufacturerToSupplier($supplierManufacturer, $manufacturer);

        return $manufacturer;
    }

    private function manufacturerAlreadyExists(string $name): ?Manufacturer
    {
        return $this->entityManager->getRepository(Manufacturer::class)->findOneBy(['name' => $name]);
    }

    private function mapManufacturerToSupplier(
        SupplierManufacturer $supplierManufacturer,
        Manufacturer $manufacturer,
    ): void {
        if ($supplierManufacturer->getMappedManufacturer() instanceof Manufacturer) {
            return;
        }

        $manufacturer->addSupplierManufacturer($supplierManufacturer);

        $this->entityManager->persist($supplierManufacturer);
        $this->entityManager->flush();
    }
}
