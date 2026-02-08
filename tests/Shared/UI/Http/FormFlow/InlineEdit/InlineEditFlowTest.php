<?php

namespace App\Tests\Shared\UI\Http\FormFlow\InlineEdit;

use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\ManufacturerFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class InlineEditFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testManufacturerNameInlineEditDisplays(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Old Name']);
        $publicId = $manufacturer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $publicId . '/inline/name')
            ->assertSuccessful()
            ->assertSee('Old Name');
    }

    public function testManufacturerNameInlineEditForm(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Old Name']);
        $publicId = $manufacturer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $publicId . '/inline/name?edit=1')
            ->assertSuccessful()
            ->assertSeeElement('input[name="inline_field[value]"]');
    }

    public function testManufacturerNameInlineEditSubmit(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Old Name']);
        $publicId = $manufacturer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'New Name')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertSee('New Name');
    }

    public function testSupplierNameInlineEditDisplays(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Supplier One']);
        $publicId = $supplier->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $publicId . '/inline/name')
            ->assertSuccessful()
            ->assertSee('Supplier One');
    }

    public function testSupplierNameInlineEditSubmit(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Supplier One']);
        $publicId = $supplier->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Updated Supplier')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertSee('Updated Supplier');
    }

    public function testCategoryNameInlineEditDisplays(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Category A']);
        $publicId = $category->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $publicId . '/inline/name')
            ->assertSuccessful()
            ->assertSee('Category A');
    }

    public function testCategoryNameInlineEditSubmit(): void
    {
        $category = CategoryFactory::createOne(['name' => 'Category A']);
        $publicId = $category->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Category B')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertSee('Category B');
    }

    public function testSubcategoryNameInlineEditDisplays(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Sub One']);
        $publicId = $subcategory->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/' . $publicId . '/inline/name')
            ->assertSuccessful()
            ->assertSee('Sub One');
    }

    public function testSubcategoryNameInlineEditSubmit(): void
    {
        $subcategory = SubcategoryFactory::createOne(['name' => 'Sub One']);
        $publicId = $subcategory->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Sub Two')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertSee('Sub Two');
    }

    public function testVatRateNameInlineEditDisplays(): void
    {
        $vatRate = VatRateFactory::createOne(['name' => 'Standard', 'rate' => '20.00']);
        $publicId = $vatRate->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/' . $publicId . '/inline/name')
            ->assertSuccessful()
            ->assertSee('Standard');
    }

    public function testVatRateNameInlineEditSubmit(): void
    {
        $vatRate = VatRateFactory::createOne(['name' => 'Standard', 'rate' => '20.00']);
        $publicId = $vatRate->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/vat-rate/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Reduced')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertSee('Reduced');
    }

    public function testInlineEditValidationError(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Valid Name']);
        $publicId = $manufacturer->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/manufacturer/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', '')
            ->click('Save (Enter)')
            ->assertStatus(422);
    }

    public function testInlineEditRequiresAuthentication(): void
    {
        $manufacturer = ManufacturerFactory::createOne(['name' => 'Test']);
        $publicId = $manufacturer->getPublicId()->value();

        $this->browser()
            ->interceptRedirects()
            ->get('/manufacturer/' . $publicId . '/inline/name')
            ->assertRedirected();
    }
}
