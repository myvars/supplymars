<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\SupplierFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateSupplierFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulEditViaForm(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Before Edit', 'isActive' => true]);
        $publicId = $supplier->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $publicId . '/edit')
            ->fillField('supplier[name]', 'After Edit')
            ->uncheckField('supplier[isActive]')
            ->click('Update Supplier')
            ->assertOn('/supplier/' . $publicId)
            ->assertSee('After Edit');
    }

    public function testValidationErrorOnEmptyName(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Will Fail', 'isActive' => true]);
        $publicId = $supplier->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $publicId . '/edit')
            ->fillField('supplier[name]', '')
            ->click('Update Supplier')
            ->assertOn('/supplier/' . $publicId . '/edit')
            ->assertSee('Please enter a supplier name');
    }

    public function testInlineEditRejectsOverLengthName(): void
    {
        $supplier = SupplierFactory::createOne(['name' => 'Old Name']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $supplier->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', str_repeat('a', 256))
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Max 255 characters') // the field's error, rendered inside the form
            ->assertSeeElement('input[name="inline_field[value]"]');

        self::assertSame('Old Name', $supplier->getName());
    }

    public function testInlineEditRejectsBlankName(): void
    {
        $entity = SupplierFactory::createOne(['name' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', '')
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Please enter a supplier name');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineRenameRefreshesThePage(): void
    {
        $entity = SupplierFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Renamed Value')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertContains('<turbo-stream action="refresh" scroll="preserve"></turbo-stream>')
            ->assertNotContains('Renamed Value')
            ->get('/supplier/' . $publicId)
            ->assertSeeIn('nav[aria-label="breadcrumb"]', 'Renamed Value');
    }
}
