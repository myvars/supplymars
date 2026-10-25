<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class UpdateSubcategoryFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

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

    public function testInlineEditUpdatesTheBreadcrumb(): void
    {
        $entity = SubcategoryFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();
        $frameId = 'inline-edit-subcategory-' . $publicId . '-name';

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/' . $publicId)
            ->assertSeeElement('nav[aria-label="breadcrumb"] [data-inline-edit-mirror="' . $frameId . '"]')
            ->get('/subcategory/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Renamed Value')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertContains('targets="[data-inline-edit-mirror=\'' . $frameId . '\']"')
            ->assertContains('<template>Renamed Value</template>');
    }
}
