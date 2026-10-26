<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class UpdateCategoryFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

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
}
