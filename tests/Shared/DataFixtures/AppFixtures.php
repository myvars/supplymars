<?php

namespace App\Tests\Shared\DataFixtures;

use App\Tests\Shared\Factory\AddressFactory;
use App\Tests\Shared\Factory\SupplierCategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\SupplierManufacturerFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\SupplierSubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Doctrine\Bundle\FixturesBundle\Fixture;
use Doctrine\Persistence\ObjectManager;

class AppFixtures extends Fixture
{
    public const int DEFAULT_SUPPLIER_PRODUCT_COUNT = 100;

    public const int EDI_SUPPLIER_COUNT = 3;

    public const int EDI_SUPPLIER_PRODUCT_COUNT = 100;

    public const int EDI_SUPPLIER_COMMON_PRODUCT_PERCENT = 50;

    public function load(ObjectManager $manager): void
    {
        $this->createUser();

        $this->createVatRates();

        $warehouse = $this->createWarehouse();
        $this->createSupplierProducts($warehouse, self::DEFAULT_SUPPLIER_PRODUCT_COUNT);

        $this->createAdditionalSuppliers($warehouse);
    }

    public function createUser(): void
    {
        $user = UserFactory::createOne([
            'fullName' => 'Adam Ashmore',
            'email' => 'adam@admin.com',
            'password' => 'letmein',
        ])->setStaff(true);

        AddressFactory::createOne([
            'email' => $user->getEmail(),
            'fullName' => $user->getFullName(),
            'isDefaultBillingAddress' => true,
            'isDefaultShippingAddress' => true,
            'customer' => $user,
        ]);
    }

    public function createVatRates(): void
    {
        VatRateFactory::new()->withStandardRate()->create();
        VatRateFactory::createOne(['name' => 'Reduced rate', 'rate' => '5.00']);
        VatRateFactory::createOne(['name' => 'Zero rate', 'rate' => '0.00']);
    }

    public function createWarehouse(): object
    {
        return SupplierFactory::new()->asWarehouse()->create();
    }

    public function createAdditionalSuppliers(object $warehouse): void
    {
        $mappedProductCount = (int) floor(
            self::EDI_SUPPLIER_PRODUCT_COUNT * (self::EDI_SUPPLIER_COMMON_PRODUCT_PERCENT / 100)
        );

        $newProductCount = self::EDI_SUPPLIER_PRODUCT_COUNT - $mappedProductCount;

        for ($i = 0; $i < self::EDI_SUPPLIER_COUNT; ++$i) {
            $supplier = SupplierFactory::createOne(['isActive' => true]);
            $this->createSupplierProducts($supplier, $newProductCount);
            $this->createCommonProducts($warehouse, $supplier, $mappedProductCount);
        }
    }

    public function createSupplierProducts(object $supplier, int $productCount): void
    {
        if ($productCount < 1) {
            return;
        }

        $categoryCount = max(1, intdiv($productCount, 10));
        $manufacturerCount = max(1, intdiv($productCount, 3));

        SupplierManufacturerFactory::createMany($manufacturerCount, ['supplier' => $supplier]);

        $categories = SupplierCategoryFactory::createMany($categoryCount, ['supplier' => $supplier]);

        foreach ($categories as $category) {
            SupplierSubcategoryFactory::new([
                'supplier' => $supplier,
                'supplierCategory' => $category,
            ])->range(1, 5)->create();
        }

        SupplierProductFactory::createMany($productCount, function () use ($supplier): array {
            $randomSubcategory = SupplierSubcategoryFactory::random([
                'supplier' => $supplier,
            ]);

            return [
                'supplier' => $supplier,
                'supplierCategory' => $randomSubcategory->getSupplierCategory(),
                'supplierSubcategory' => $randomSubcategory,
                'supplierManufacturer' => SupplierManufacturerFactory::random(['supplier' => $supplier]),
                'isActive' => true,
            ];
        });
    }

    private function createCommonProducts(object $warehouse, object $supplier, int $productMapCount): void
    {
        $commonProducts = SupplierProductFactory::randomSet(max(1, $productMapCount), [
            'supplier' => $warehouse,
        ]);

        foreach ($commonProducts as $commonProduct) {
            $supplierCategory = SupplierCategoryFactory::findOrCreate([
                'supplier' => $supplier,
                'name' => $commonProduct->getSupplierCategory()->getName(),
            ]);

            $supplierSubcategory = SupplierSubcategoryFactory::findOrCreate([
                'supplier' => $supplier,
                'supplierCategory' => $supplierCategory,
                'name' => $commonProduct->getSupplierSubcategory()->getName(),
            ]);

            $supplierManufacturer = SupplierManufacturerFactory::findOrCreate([
                'supplier' => $supplier,
                'name' => $commonProduct->getSupplierManufacturer()->getName(),
            ]);

            SupplierProductFactory::createOne([
                'name' => $commonProduct->getName(),
                'supplierCategory' => $supplierCategory,
                'supplierSubcategory' => $supplierSubcategory,
                'supplierManufacturer' => $supplierManufacturer,
                'mfrPartNumber' => $commonProduct->getMfrPartNumber(),
                'supplier' => $supplier,
                'weight' => $commonProduct->getWeight(),
                'cost' => $commonProduct->getCost() * (1 + (random_int(-20, 20) / 100)),
                'isActive' => true,
            ]);
        }
    }
}
