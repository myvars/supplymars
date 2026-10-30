<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateProductFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testSuccessfulUpdateViaForm(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/edit')
            ->fillField('product[name]', 'Flow Updated Product')
            ->fillField('product[mfrPartNumber]', 'FLOW-9876')
            ->fillField('product[isActive]', '1')
            ->click('Update Product')
            ->assertOn('/product/' . $publicId)
            ->assertSee('Flow Updated Product');
    }

    public function testUpdateOfNeverSourcedProduct(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::createOne();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/edit')
            ->fillField('product[name]', 'Unsourced Product')
            ->click('Update Product')
            ->assertOn('/product/' . $publicId)
            ->assertSee('Unsourced Product');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $product = ProductFactory::new()->withActiveSource()->create();
        $publicId = $product->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/product/' . $publicId . '/edit')
            ->fillField('product[name]', '')
            ->fillField('product[mfrPartNumber]', '')
            ->click('Update Product')
            ->assertOn('/product/' . $publicId . '/edit')
            ->assertSee('Please enter a product name')
            ->assertSee('Please enter a manufacturer part number');
    }

    public function testInlineEditRejectsOverLengthName(): void
    {
        $entity = ProductFactory::createOne(['name' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', str_repeat('a', 256))
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Max 255 characters') // the field's error, rendered inside the form
            ->assertSeeElement('input[name="inline_field[value]"]');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineEditRejectsBlankName(): void
    {
        $entity = ProductFactory::createOne(['name' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', '')
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Please enter a product name');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineRenameRefreshesThePage(): void
    {
        $entity = ProductFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Renamed Value')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertContains('<turbo-stream action="refresh" scroll="preserve"></turbo-stream>')
            ->assertNotContains('Renamed Value')
            ->get('/product/' . $publicId)
            ->assertSeeIn('nav[aria-label="breadcrumb"]', 'Renamed Value');
    }
}
