<?php

namespace App\Tests\Integration\Entity;

use App\Entity\PriceModel;
use App\Entity\Product;
use App\Factory\CategoryFactory;
use App\Factory\ManufacturerFactory;
use App\Factory\ProductFactory;
use App\Factory\SubcategoryFactory;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ProductTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;
    private EntityManagerInterface $entityManager;

    public function setUp(): void
    {
        $this->validator = static::getContainer()->get('validator');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateReadUpdateDeleteProduct(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Test Subcategory'])->object();
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Test Manufacturer'])->object();
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->object();

        $product = new Product();
        $product
            ->setName('Test Product')
            ->setMfrPartNumber('Test MfrPartNumber')
            ->setStock(1)
            ->setLeadTimeDays(1)
            ->setWeight(1)
            ->setDefaultMarkup(0.21)
            ->setCost(100)
            ->setCategory($subcategory->getCategory())
            ->setSubcategory($subcategory)
            ->setManufacturer($manufacturer)
            ->setOwner($owner)
            ->setPriceModel(PriceModel::PRETTY_99)
            ->setIsActive(true);

        $this->entityManager->persist($product);
        $this->entityManager->flush();

        $this->assertNotNull($product->getId());

        $product->setName('Updated Product');
        $this->entityManager->flush();

        $this->assertEquals('Updated Product', $product->getName());

        $this->entityManager->remove($product);
        $this->entityManager->flush();

        $this->assertNull($product->getId());
    }

    public function testProductCategoryIsMissing(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Test Subcategory'])->object();
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Test Manufacturer'])->object();
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->object();

        $product = new Product();
        $product
            ->setName('Test Product')
            ->setMfrPartNumber('Test MfrPartNumber')
            ->setStock(1)
            ->setLeadTimeDays(1)
            ->setWeight(1)
            ->setDefaultMarkup(0.21)
            ->setCost(100)
            ->setSubcategory($subcategory)
            ->setManufacturer($manufacturer)
            ->setOwner($owner)
            ->setPriceModel(PriceModel::PRETTY_99);

        $result = $this->validator->validate($product);
        $this->assertCount(1, $result);
        $this->assertEquals('Please enter a category', $result[0]->getMessage());
    }

    public function testProductSubcategoryIsMissing(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Test Category'])->object();
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Test Manufacturer'])->object();
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->object();

        $product = new Product();
        $product
            ->setName('Test Product')
            ->setMfrPartNumber('Test MfrPartNumber')
            ->setStock(1)
            ->setLeadTimeDays(1)
            ->setWeight(1)
            ->setDefaultMarkup(0.21)
            ->setCost(100)
            ->setCategory($category)
            ->setManufacturer($manufacturer)
            ->setOwner($owner)
            ->setPriceModel(PriceModel::PRETTY_99);

        $result = $this->validator->validate($product);
        $this->assertCount(1, $result);
        $this->assertEquals('Please enter a subcategory', $result[0]->getMessage());
    }

    public function testProductActiveMarkupAndTarget(): void
    {
        $category = CategoryFactory::createOne([
            'name' => 'Test Category',
            'defaultMarkup' => '2.000',
            'isActive' => true
        ])->object();
        $subcategory = SubcategoryFactory::createOne([
            'name' => 'Test Subcategory',
            'category' => $category,
            'defaultMarkup' => '5.000',
            'isActive' => true
        ])->object();

        $product = new Product();
        $product
            ->setName('Test Product')
            ->setDefaultMarkup('10.000')
            ->setCost(100)
            ->setCategory($category)
            ->setSubcategory($subcategory)
            ->setPriceModel(PriceModel::PRETTY_99)
            ->setIsActive(true);

        $this->assertEquals('10.000', $product->getActiveMarkup());
        $this->assertEquals('PRODUCT', $product->getActiveMarkupTarget());

        $product->setDefaultMarkup('0.000');
        $this->assertEquals('5.000', $product->getActiveMarkup());
        $this->assertEquals('SUBCATEGORY', $product->getActiveMarkupTarget());

        $subcategory->setDefaultMarkup('0.000');
        $this->assertEquals('2.000', $product->getActiveMarkup());
        $this->assertEquals('CATEGORY', $product->getActiveMarkupTarget());
    }

    public function testProductFindBySearch(): void
    {
        ProductFactory::createOne(['name' => 'Test Product A']);
        ProductFactory::createOne(['name' => 'Test Product B']);

        $products = $this->entityManager->getRepository(Product::class)->findBySearch('Test Product', 1);
        $this->assertCount(1, $products);
    }

    public function testProductFindBySearchQueryBuilder(): void
    {
        ProductFactory::createOne(['name' => 'Test Product A']);
        ProductFactory::createOne(['name' => 'Test Product B']);

        $products = $this->entityManager
            ->getRepository(Product::class)
            ->findBySearchQueryBuilder('Test Product', 'name', 'asc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $products);
        $this->assertEquals('Test Product A', $products[0]->getName());
        $this->assertEquals('Test Product B', $products[1]->getName());

        $products = $this->entityManager
            ->getRepository(Product::class)
            ->findBySearchQueryBuilder('Test Product', 'name', 'desc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $products);
        $this->assertEquals('Test Product B', $products[0]->getName());
        $this->assertEquals('Test Product A', $products[1]->getName());
    }

    /**
     * @dataProvider getValidationTestCases
     */
    public function testSupplierProductValidation(
        $name,
        $mfrPartNumber,
        $stock,
        $leadTimeDays,
        $weight,
        $defaultMarkup,
        $markup,
        $cost,
        $sellPrice,
        $sellPriceIncVat,
        $priceModel,
        $isActive,
        $expected
    ): void {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Test Subcategory'])->object();
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Test Manufacturer'])->object();
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->object();

        $product = new Product();
        $product
            ->setName($name)
            ->setMfrPartNumber($mfrPartNumber)
            ->setStock($stock)
            ->setLeadTimeDays($leadTimeDays)
            ->setWeight($weight)
            ->setDefaultMarkup($defaultMarkup)
            ->setMarkup($markup)
            ->setCost($cost)
            ->setSellPrice($sellPrice)
            ->setSellPriceIncVat($sellPriceIncVat)
            ->setCategory($subcategory->getCategory())
            ->setSubcategory($subcategory)
            ->setManufacturer($manufacturer)
            ->setOwner($owner)
            ->setPriceModel($priceModel);

        $result = $this->validator->validate($product);
        $this->assertEquals($expected, count($result) === 0);
    }

    public function getValidationTestCases(): array
    {
        return [

            'Succeeds when data is correct' => ['Test Product', 'Test MfrPartNumber', 1, 1, 1, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, true],
            'Fails when name is missing' => ['', 'Test MfrPartNumber', 1, 1, 1, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when mfrPartNumber is missing' => ['Test Product', '', 1, 1, 1, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when stock is less than 0' => ['Test Product', 'Test MfrPartNumber', -1, 1, 1, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when stock is greater than 10000' => ['Test Product', 'Test MfrPartNumber', 10001, 1, 1, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when LeadTimeDays is missing' => ['Test Product', 'Test MfrPartNumber', 1, null, 1, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when leadTimeDays is less than 0' => ['Test Product', 'Test MfrPartNumber', 1, -1, 1, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when LeadTimeDays is greater than 1000' => ['Test Product', 'Test MfrPartNumber', 1, 1001, 1, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when weight is missing' => ['Test Product', 'Test MfrPartNumber', 1, 1, null, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when weight is less than 0' => ['Test Product', 'Test MfrPartNumber', 1, 1, -1, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when weight is greater than 100000' => ['Test Product', 'Test MfrPartNumber', 1, 1, 100001, 0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when defaultMarkup is missing' => ['Test Product', 'Test MfrPartNumber', 1, 1, 1, null, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when defaultMarkup is less than 0' => ['Test Product', 'Test MfrPartNumber', 1, 1, 1, -0.21, 0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when markup is less than 0' => ['Test Product', 'Test MfrPartNumber', 1, 1, 1, 0.21, -0.21, 100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when cost is missing' => ['Test Product', 'Test MfrPartNumber', 1, 1, 1, 0.21, 0.21, null, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when cost is less than 0' => ['Test Product', 'Test MfrPartNumber', 1, 1, 1, 0.21, 0.21, -100, 150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when sellPrice is less than 0' => ['Test Product', 'Test MfrPartNumber', 1, 1, 1, 0.21, 0.21, 100, -150, 180, PriceModel::PRETTY_99, true, false],
            'Fails when SellPriceIncVat is less than 0' => ['Test Product', 'Test MfrPartNumber', 1, 1, 1, 0.21, 0.21, 100, 150, -180, PriceModel::PRETTY_99, true, false],
            'Fails when priceModel is missing' => ['Test Product', 'Test MfrPartNumber', 1, 1, 1, 0.21, 0.21, 100, 150, 180, null, true, false],
        ];
    }
}