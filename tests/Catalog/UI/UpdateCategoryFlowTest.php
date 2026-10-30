<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\UI\PostedRecordId;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class UpdateCategoryFlowTest extends WebTestCase
{
    use HasBrowser;
    use PostedRecordId;

    #[DataProvider('postedRecordIds')]
    public function testEditRefusesACategoryIdPostedInTheForm(string $postedId): void
    {
        $other = CategoryFactory::createOne(['name' => 'Other']);
        $category = CategoryFactory::createOne(['name' => 'Before Edit']);

        $this->assertRefusesPostedRecordId(
            $this->browser()->actingAs(UserFactory::new()->asStaff()->create()),
            '/category/' . $category->getPublicId()->value() . '/edit',
            'Update Category',
            'id',
            $postedId,
            $other->getPublicId()->value(),
            ['name' => 'Renamed'],
        );

        CategoryFactory::assert()->exists(['id' => $other->getId(), 'name' => 'Other']);
        CategoryFactory::assert()->exists(['id' => $category->getId(), 'name' => 'Before Edit']);
    }

    public function testSuccessfulEditViaForm(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $vatRate = VatRateFactory::createOne();

        $category = CategoryFactory::createOne([
            'name' => 'Before Edit',
            'owner' => $owner,
            'vatRate' => $vatRate,
            'defaultMarkup' => '5.000',
            'priceModel' => PriceModel::DEFAULT,
            'isActive' => true,
        ]);

        $publicId = $category->getPublicId()->value();

        $owner2 = UserFactory::new()->asStaff()->create();
        $vatRate2 = VatRateFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $publicId . '/edit')
            ->fillField('category[name]', 'After Edit')
            ->fillField('category[vatRate]', (string) $vatRate2->getId())
            ->fillField('category[defaultMarkup]', '7.500')
            ->fillField('category[priceModel]', PriceModel::PRETTY_99->value)
            ->fillField('category[owner]', (string) $owner2->getId())
            ->uncheckField('category[isActive]')
            ->click('Update Category')
            ->assertOn('/category/' . $publicId)
            ->assertSee('After Edit');
    }

    public function testValidationErrorOnEmptyName(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $vatRate = VatRateFactory::createOne();

        $category = CategoryFactory::createOne([
            'name' => 'To Edit',
            'owner' => $owner,
            'vatRate' => $vatRate,
            'defaultMarkup' => '5.000',
            'priceModel' => PriceModel::DEFAULT,
            'isActive' => true,
        ]);

        $publicId = $category->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $publicId . '/edit')
            ->fillField('category[name]', '')
            ->click('Update Category')
            ->assertOn('/category/' . $publicId . '/edit')
            ->assertSee('Please enter a category name');
    }

    public function testInlineEditRejectsOverLengthName(): void
    {
        $entity = CategoryFactory::createOne(['name' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', str_repeat('a', 256))
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Max 255 characters') // the field's error, rendered inside the form
            ->assertSeeElement('input[name="inline_field[value]"]');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineEditRejectsBlankName(): void
    {
        $entity = CategoryFactory::createOne(['name' => 'Old Value']);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $entity->getPublicId()->value() . '/inline/name?edit=1')
            ->fillField('inline_field[value]', '')
            ->click('Save (Enter)')
            ->assertStatus(422)
            ->assertSeeIn('form', 'Please enter a category name');

        self::assertSame('Old Value', $entity->getName());
    }

    public function testInlineRenameRefreshesThePage(): void
    {
        $entity = CategoryFactory::createOne(['name' => 'Old Value']);
        $publicId = $entity->getPublicId()->value();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/' . $publicId . '/inline/name?edit=1')
            ->fillField('inline_field[value]', 'Renamed Value')
            ->click('Save (Enter)')
            ->assertSuccessful()
            ->assertContains('<turbo-stream action="refresh" scroll="preserve"></turbo-stream>')
            ->assertNotContains('Renamed Value')
            ->get('/category/' . $publicId)
            ->assertSeeIn('nav[aria-label="breadcrumb"]', 'Renamed Value');
    }
}
