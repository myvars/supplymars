<?php

namespace App\Tests\Integration\Entity;

use App\Entity\Category;
use App\Enum\PriceModel;
use App\Factory\CategoryFactory;
use App\Factory\UserFactory;
use App\Factory\VatRateFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class CategoryTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->validator = static::getContainer()->get('validator');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateReadUpdateDeleteCategory(): void
    {
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->_real();
        $vatRate = VatRateFactory::createOne(['name' => 'Test VatRate'])->_real();

        $category = new Category();
        $category
            ->setName('Test Category')
            ->setDefaultMarkup(0.21)
            ->setOwner($owner)
            ->setVatRate($vatRate)
            ->setPriceModel(PriceModel::DEFAULT)
            ->setIsActive(true);

        $this->entityManager->persist($category);
        $this->entityManager->flush();

        $this->assertNotNull($category->getId());

        $category->setName('Updated Category');
        $this->entityManager->flush();

        $this->assertEquals('Updated Category', $category->getName());

        $this->entityManager->remove($category);
        $this->entityManager->flush();

        $this->assertNull($category->getId());
    }

    public function testCategoryVatRateIsMissing(): void
    {
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->_real();

        $category = new Category();
        $category
            ->setName('Test Category')
            ->setDefaultMarkup(0.21)
            ->setOwner($owner)
            ->setPriceModel(PriceModel::DEFAULT)
            ->setIsActive(true);

        $result = $this->validator->validate($category);
        $this->assertCount(1, $result);
        $this->assertEquals('Please enter a VAT rate', $result[0]->getMessage());
    }

    public function testCategoryOwnerIsMissing(): void
    {
        $vatRate = VatRateFactory::createOne(['name' => 'Test VatRate'])->_real();

        $category = new Category();
        $category
            ->setName('Test Category')
            ->setDefaultMarkup(0.21)
            ->setVatRate($vatRate)
            ->setPriceModel(PriceModel::DEFAULT)
            ->setIsActive(true);

        $result = $this->validator->validate($category);
        $this->assertCount(1, $result);
        $this->assertEquals('Please enter a category owner', $result[0]->getMessage());
    }

    public function testCategoryFindBySearch(): void
    {
        CategoryFactory::createOne(['name' => 'Test Category A']);
        CategoryFactory::createOne(['name' => 'Test Category B']);

        $categories = $this->entityManager->getRepository(Category::class)->findBySearch('Test Category', 1);
        $this->assertCount(1, $categories);
    }

    public function testCategoryFindBySearchQueryBuilder(): void
    {
        CategoryFactory::createOne(['name' => 'Test Category A']);
        CategoryFactory::createOne(['name' => 'Test Category B']);

        $categories = $this->entityManager
            ->getRepository(Category::class)
            ->findBySearchQueryBuilder('Test Category', 'name', 'asc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $categories);
        $this->assertEquals('Test Category A', $categories[0]->getName());
        $this->assertEquals('Test Category B', $categories[1]->getName());

        $categories = $this->entityManager
            ->getRepository(Category::class)
            ->findBySearchQueryBuilder('Test Category', 'name', 'desc')
            ->getQuery()
            ->getResult();

        $this->assertCount(2, $categories);
        $this->assertEquals('Test Category B', $categories[0]->getName());
        $this->assertEquals('Test Category A', $categories[1]->getName());
    }

    /**
     * @dataProvider getValidationTestCases
     */
    public function testCategoryValidation(
        string $name,
        string $defaultMarkup,
        ?PriceModel $priceModel,
        bool $isActive,
        bool $expected
    ): void {
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->_real();
        $vatRate = VatRateFactory::createOne(['name' => 'Test VatRate'])->_real();

        $category = new Category();
        $category
            ->setName($name)
            ->setDefaultMarkup($defaultMarkup)
            ->setOwner($owner)
            ->setVatRate($vatRate)
            ->setPriceModel($priceModel)
            ->setIsActive($isActive);

        $result = $this->validator->validate($category);
        $this->assertEquals($expected, count($result) === 0);
    }

    public function getValidationTestCases(): array
    {
        return [
            'Succeeds when data is correct' => ['Test Category', '0.21', PriceModel::DEFAULT, true, true],
            'Fails when name is missing' => ['', '0.21', PriceModel::DEFAULT, true, false],
            'Fails when defaultMarkup is less than 0' => ['Test Category', '-0.21', PriceModel::DEFAULT, true, false],
            'Fails when priceModel is missing' => ['Test Category', '0.21', null, true, false],
        ];
    }
}