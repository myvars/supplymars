<?php

namespace App\Purchasing\Application\Service;

use App\Catalog\Domain\Model\Category\Category;
use App\Catalog\Domain\Model\Manufacturer\Manufacturer;
use App\Catalog\Domain\Model\Product\Product;
use App\Catalog\Domain\Model\Subcategory\Subcategory;
use App\Customer\Domain\Model\User\User;
use App\Pricing\Domain\Model\VatRate\VatRate;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierCategory;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierManufacturer;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProduct;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierSubcategory;
use App\Shared\Domain\Service\Pricing\MarkupCalculator;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class SupplierProductMappingService
{
    private const string DEFAULT_OWNER = 'adam@admin.com';

    public function __construct(
        private EntityManagerInterface $em,
        private MarkupCalculator $markupCalculator,
        private ValidatorInterface $validator,
        private CurrentUserProvider $currentUserProvider,
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
        $manufacturer = $this->em->getRepository(Manufacturer::class)->findOneBy(['name' => $sm->getName()]);
        if (!$manufacturer instanceof Manufacturer) {
            $manufacturer = Manufacturer::create(
                name: $sm->getName(),
                isActive: true
            );

            $this->validate($manufacturer);
            $this->em->persist($manufacturer);
        }

        $mappedManufacturer = $sm->getMappedManufacturer();
        if (!$mappedManufacturer instanceof Manufacturer) {
            $manufacturer->addSupplierManufacturer($sm);
            $this->em->persist($sm);
        }

        return $manufacturer;
    }

    private function getOrCreateCategory(SupplierCategory $sc): Category
    {
        $category = $this->em->getRepository(Category::class)->findOneBy(['name' => $sc->getName()]);
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
        $this->em->persist($category);
        return $category;
    }

    private function getOrCreateSubcategory(SupplierSubcategory $ss, Category $category): Subcategory
    {
        $subcategory = $this->em->getRepository(Subcategory::class)->findOneBy(['name' => $ss->getName()]);
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
            $this->em->persist($subcategory);
        }

        $mappedSubcategory = $ss->getMappedSubcategory();
        if (!$mappedSubcategory instanceof Subcategory) {
            $subcategory->addSupplierSubcategory($ss);
            $this->em->persist($ss);
        }

        return $subcategory;
    }

    private function getOrCreateProduct(
        SupplierProduct $sp,
        Manufacturer $manufacturer,
        Subcategory $subcategory
    ): Product {
        $product = $this->em->getRepository(Product::class)->findOneBy(['name' => $sp->getName()]);
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
            $this->em->persist($product);
        }

        $mappedProduct = $sp->getProduct();
        if (!$mappedProduct instanceof Product) {
            $product->addSupplierProduct($this->markupCalculator, $sp);
            $this->em->persist($sp);
        }

        return $product;
    }

    private function getDefaultOwner(): User
    {
        $owner = $this->em->getRepository(User::class)->findOneBy([
            'email' => self::DEFAULT_OWNER,
            'isStaff' => true
        ]);
        if (!$owner instanceof User) {
            throw new \RuntimeException('Default owner not found');
        }

        return $owner;
    }

    private function getDefaultVatRate(): VatRate
    {
        $rate = $this->em->getRepository(VatRate::class)->findOneBy(['isDefaultVatRate' => true]);
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
