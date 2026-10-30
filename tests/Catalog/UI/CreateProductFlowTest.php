<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class CreateProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulCreationViaForm(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $owner = UserFactory::new()->asStaff()->create();
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['category' => $category]);
        $manufacturer = ManufacturerFactory::createOne();

        $this->browser()
            ->actingAs($user)
            ->get('/product/new')
            ->fillField('product[name]', 'Flow Product')
            ->fillField('product[description]', 'Flow product description')
            ->fillField('product[category]', (string) $category->getId())
            ->click('product_auto-update')
            ->fillField('product[subcategory]', (string) $subcategory->getId())
            ->fillField('product[manufacturer]', (string) $manufacturer->getId())
            ->fillField('product[mfrPartNumber]', 'FLOW-1234')
            ->fillField('product[owner]', (string) $owner->getId())
            ->fillField('product[isActive]', '1')
            ->click('Create Product')
            ->assertSuccessful()
            ->assertSee('Flow Product')
            ->assertNotOn('/product/');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs($user)
            ->get('/product/new')
            ->click('Create Product')
            ->assertOn('/product/new')
            ->assertSee('Please enter a product name')
            ->assertSee('Please choose a Category')
            ->assertSee('Please choose a Subcategory')
            ->assertSee('Please choose a Manufacturer')
            ->assertSee('Please enter a manufacturer part number');
    }

    #[DataProvider('overLengthFields')]
    public function testRejectsOverLengthValueAsFieldError(string $field): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $category = CategoryFactory::createOne();
        $subcategory = SubcategoryFactory::createOne(['category' => $category]);
        $manufacturer = ManufacturerFactory::createOne();

        $values = ['name' => 'Flow Product', 'description' => 'Flow product description', 'mfrPartNumber' => 'FLOW-1234'];
        $values[$field] = str_repeat('a', 256);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/new')
            ->fillField('product[name]', $values['name'])
            ->fillField('product[description]', $values['description'])
            ->fillField('product[category]', (string) $category->getId())
            ->click('product_auto-update')
            ->fillField('product[subcategory]', (string) $subcategory->getId())
            ->fillField('product[manufacturer]', (string) $manufacturer->getId())
            ->fillField('product[mfrPartNumber]', $values['mfrPartNumber'])
            ->fillField('product[owner]', (string) $owner->getId())
            ->fillField('product[isActive]', '1')
            ->click('Create Product')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Max 255 characters')
            ->assertNotSee('Object(');

        ProductFactory::assert()->empty();
    }

    /**
     * @return iterable<string, array{string}>
     */
    public static function overLengthFields(): iterable
    {
        yield 'name' => ['name'];
        yield 'description' => ['description'];
        yield 'mfrPartNumber' => ['mfrPartNumber'];
    }
}
