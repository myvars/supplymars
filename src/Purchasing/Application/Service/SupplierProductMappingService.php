<?php

declare(strict_types=1);

namespace App\Purchasing\Application\Service;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Catalog\Domain\Repository\CategoryRepository;
use App\Catalog\Domain\Repository\ManufacturerRepository;
use App\Catalog\Domain\Repository\ProductRepository;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Customer\Infrastructure\Security\CurrentUserProvider;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Pricing\Domain\Repository\VatRateRepository;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class SupplierProductMappingService
{
    public function __construct(
        private ManufacturerRepository $manufacturers,
        private CategoryRepository $categories,
        private SubcategoryRepository $subcategories,
        private ProductRepository $products,
        private UserRepository $users,
        private VatRateRepository $vatRates,
        private MarkupCalculator $markupCalculator,
        private ValidatorInterface $validator,
        private CurrentUserProvider $currentUserProvider,
        #[Autowire(env: 'DEFAULT_STAFF_EMAIL')]
        private string $defaultStaffEmail,
    ) {
    }

    public function map(SupplierProduct $supplierProduct): Product
    {
        $supplierManufacturer = $supplierProduct->getSupplierManufacturer();
        if (!$supplierManufacturer instanceof SupplierManufacturer) {
            throw new \InvalidArgumentException('Supplier manufacturer is missing');
        }

        $supplierCategory = $supplierProduct->getSupplierCategory();
        if (!$supplierCategory instanceof SupplierCategory) {
            throw new \InvalidArgumentException('Supplier category is missing');
        }

        $supplierSubcategory = $supplierProduct->getSupplierSubcategory();
        if (!$supplierSubcategory instanceof SupplierSubcategory) {
            throw new \InvalidArgumentException('Supplier subcategory is missing');
        }

        $manufacturer = $this->getOrCreateManufacturer($supplierManufacturer);
        $category = $this->getOrCreateCategory($supplierCategory);
        $subcategory = $this->getOrCreateSubcategory($supplierSubcategory, $category);

        return $this->getOrCreateProduct($supplierProduct, $manufacturer, $subcategory);
    }

    private function getOrCreateManufacturer(SupplierManufacturer $sm): Manufacturer
    {
        $manufacturer = $this->manufacturers->getByName($sm->getName());
        if (!$manufacturer instanceof Manufacturer) {
            $manufacturer = Manufacturer::create(
                name: $sm->getName(),
                isActive: true
            );

            $this->validate($manufacturer);
            $this->manufacturers->add($manufacturer);
        }

        $mappedManufacturer = $sm->getMappedManufacturer();
        if (!$mappedManufacturer instanceof Manufacturer) {
            $manufacturer->addSupplierManufacturer($sm);
        }

        return $manufacturer;
    }

    private function getOrCreateCategory(SupplierCategory $sc): Category
    {
        $category = $this->categories->getByName($sc->getName());
        if ($category instanceof Category) {
            return $category;
        }

        $owner = $this->currentUserProvider->hasUser() ? $this->currentUserProvider->get() : $this->getDefaultOwner();
        $vatRate = $this->getDefaultVatRate();

        $category = Category::create(
            name: $sc->getName(),
            owner: $owner,
            vatRate: $vatRate,
            defaultMarkup: Category::DEFAULT_MARKUP,
            priceModel: Category::DEFAULT_PRICE_MODEL,
            isActive: true
        );

        $this->validate($category);
        $this->categories->add($category);

        return $category;
    }

    private function getOrCreateSubcategory(SupplierSubcategory $ss, Category $category): Subcategory
    {
        $subcategory = $this->subcategories->getByName($ss->getName());
        if (!$subcategory instanceof Subcategory) {
            $subcategory = Subcategory::create(
                name: $ss->getName(),
                category: $category,
                owner: null,
                defaultMarkup: Subcategory::DEFAULT_MARKUP,
                priceModel: Subcategory::DEFAULT_PRICE_MODEL,
                isActive: true
            );

            $this->validate($subcategory);
            $this->subcategories->add($subcategory);
        }

        $mappedSubcategory = $ss->getMappedSubcategory();
        if (!$mappedSubcategory instanceof Subcategory) {
            $subcategory->addSupplierSubcategory($ss);
        }

        return $subcategory;
    }

    private function getOrCreateProduct(
        SupplierProduct $sp,
        Manufacturer $manufacturer,
        Subcategory $subcategory,
    ): Product {
        $product = $this->products->getByName($sp->getName());
        if (!$product instanceof Product) {
            $product = Product::create(
                name: $sp->getName(),
                description: null,
                category: $subcategory->getCategory(),
                subcategory: $subcategory,
                manufacturer: $manufacturer,
                mfrPartNumber: $sp->getMfrPartNumber(),
                owner: null,
                isActive: true,
            );

            $this->validate($product);
            $this->products->add($product);
        }

        $mappedProduct = $sp->getProduct();
        if (!$mappedProduct instanceof Product) {
            $product->addSupplierProduct($this->markupCalculator, $sp);
        }

        return $product;
    }

    private function getDefaultOwner(): User
    {
        $owner = $this->users->getByEmail($this->defaultStaffEmail);
        if (!$owner instanceof User || !$owner->isStaff()) {
            throw new \RuntimeException('Default owner not found');
        }

        return $owner;
    }

    private function getDefaultVatRate(): VatRate
    {
        $rate = $this->vatRates->getDefaultVatRate();
        if (!$rate instanceof VatRate) {
            throw new \RuntimeException('Default VAT rate not found');
        }

        return $rate;
    }

    private function validate(object $entity): void
    {
        $errors = $this->validator->validate($entity);
        if (\count($errors) > 0) {
            throw new \InvalidArgumentException((string) $errors);
        }
    }
}
