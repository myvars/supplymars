<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateSubcategoryFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testSuccessfulUpdateViaForm(): void
    {
        $subcategory = SubcategoryFactory::new()->create();
        $publicId = $subcategory->getPublicId()->value();
        $newCategory = CategoryFactory::new()->create();
        $newOwner = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/' . $publicId . '/edit')
            ->fillField('subcategory[category]', (string) $newCategory->getId())
            ->fillField('subcategory[name]', 'Flow Updated Subcategory')
            ->fillField('subcategory[defaultMarkup]', '7.500')
            ->fillField('subcategory[priceModel]', PriceModel::PRETTY_99->value)
            ->fillField('subcategory[owner]', (string) $newOwner->getId())
            ->uncheckField('subcategory[isActive]')
            ->click('Update Subcategory')
            ->assertOn('/subcategory/' . $publicId)
            ->assertSee('Flow Updated Subcategory');

        self::assertSame('Flow Updated Subcategory', $subcategory->getName());
        self::assertSame($newCategory->getId(), $subcategory->getCategory()->getId());
        self::assertSame('7.500', $subcategory->getDefaultMarkup());
        self::assertSame(PriceModel::PRETTY_99, $subcategory->getPriceModel());
        self::assertSame($newOwner->getId(), $subcategory->getOwner()?->getId());
        self::assertFalse($subcategory->isActive());
    }

    public function testValidationErrorsOnEmptyName(): void
    {
        $subcategory = SubcategoryFactory::new()->create();
        $publicId = $subcategory->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/' . $publicId . '/edit')
            ->fillField('subcategory[name]', '')
            ->click('Update Subcategory')
            ->assertOn('/subcategory/' . $publicId . '/edit')
            ->assertSee('Please enter a subcategory name');
    }

    public function testInlineEditRejectsOverLengthName(): void
    {
        $entity = SubcategoryFactory::createOne(['name' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', str_repeat('a', 256))
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Max 255 characters') // the field's error, rendered inside the form
            ->assertSeeElement('input[name="inline_field[value]"]');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineEditRejectsBlankName(): void
    {
        $entity = SubcategoryFactory::createOne(['name' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', '')
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Please enter a Subcategory name');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineRenameRefreshesThePage(): void
    {
        $entity = SubcategoryFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Renamed Value')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertContains('<turbo-stream action="refresh" scroll="preserve"></turbo-stream>')
            ->assertNotContains('Renamed Value')
            ->get('/subcategory/' . $publicId)
            ->assertSeeIn('nav[aria-label="breadcrumb"]', 'Renamed Value');
    }
}
