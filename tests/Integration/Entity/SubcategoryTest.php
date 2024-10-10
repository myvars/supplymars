<?php

namespace App\Tests\Integration\Entity;

use App\DTO\SearchDto\SubcategorySearchDto;
use App\Entity\Subcategory;
use App\Enum\PriceModel;
use App\Factory\CategoryFactory;
use App\Factory\SubcategoryFactory;
use App\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class SubcategoryTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        $this->validator = static::getContainer()->get('validator');
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
    }

    public function testCreateReadUpdateDeleteSubcategory(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Test Category'])->_real();
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->_real();

        $subcategory = new Subcategory();
        $subcategory
            ->setName('Test Subcategory')
            ->setCategory($category)
            ->setDefaultMarkup(0.21)
            ->setOwner($owner)
            ->setPriceModel(PriceModel::DEFAULT)
            ->setIsActive(true);

        $this->entityManager->persist($subcategory);
        $this->entityManager->flush();

        $this->assertNotNull($subcategory->getId());

        $subcategory->setName('Updated Subcategory');
        $this->entityManager->flush();

        $this->assertEquals('Updated Subcategory', $subcategory->getName());

        $this->entityManager->remove($subcategory);
        $this->entityManager->flush();

        $this->assertNull($subcategory->getId());
    }

    public function testSubcategoryCategoryIsMissing(): void
    {
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->_real();

        $subcategory = new Subcategory();
        $subcategory
            ->setName('Test Subcategory')
            ->setDefaultMarkup(0.21)
            ->setOwner($owner)
            ->setPriceModel(PriceModel::DEFAULT)
            ->setIsActive(true);

        $result = $this->validator->validate($subcategory);
        $this->assertCount(1, $result);
        $this->assertEquals('Please enter a category', $result[0]->getMessage());
    }

    public function testSubcategoryFindBySearchDto(): void
    {
        SubcategoryFactory::createOne(['name' => 'Test Subcategory A']);
        SubcategoryFactory::createOne(['name' => 'Test Subcategory B']);

        $searchDto = new SubcategorySearchDto();
        $searchDto
            ->setQuery('Test Subcategory')
            ->setSort('name')
            ->setSortDirection('asc');
        $subcategories = $this->entityManager->getRepository(Subcategory::class)
            ->findBySearchDto($searchDto)->getQuery()->getResult();

        $this->assertCount(2, $subcategories);
        $this->assertEquals('Test Subcategory A', $subcategories[0]->getName());
        $this->assertEquals('Test Subcategory B', $subcategories[1]->getName());

        $searchDto->setSortDirection('desc');
        $subcategories = $this->entityManager->getRepository(Subcategory::class)
            ->findBySearchDto($searchDto)->getQuery()->getResult();

        $this->assertCount(2, $subcategories);
        $this->assertEquals('Test Subcategory B', $subcategories[0]->getName());
        $this->assertEquals('Test Subcategory A', $subcategories[1]->getName());
    }

    /**
     * @dataProvider getValidationTestCases
     */
    public function testSubcategoryValidation(
        string $name,
        string $defaultMarkup,
        ?PriceModel $priceModel,
        bool $isActive,
        bool $expected
    ): void {
        $category = CategoryFactory::createOne(['name' => 'Test Category'])->_real();
        $owner = UserFactory::createOne(['fullName' => 'Test Owner'])->_real();

        $subcategory = new Subcategory();
        $subcategory
            ->setName($name)
            ->setCategory($category)
            ->setDefaultMarkup($defaultMarkup)
            ->setOwner($owner)
            ->setPriceModel($priceModel)
            ->setIsActive($isActive);

        $result = $this->validator->validate($subcategory);
        $this->assertEquals($expected, count($result) === 0);
    }

    public function getValidationTestCases(): array
    {
        return [
            'Succeeds when data is correct' => ['Test Subcategory', '0.21', PriceModel::DEFAULT, true, true],
            'Fails when name is missing' => ['', '0.21', PriceModel::DEFAULT, true, false],
            'Fails when defaultMarkup is less than 0' => ['Test Subcategory', '-0.21', PriceModel::DEFAULT, true, false],
            'Fails when priceModel is missing' => ['Test Subcategory', '0.21', null, true, false],
        ];
    }
}