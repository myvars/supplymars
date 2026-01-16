<?php

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
            ->fillField('category[vatRate]', $vatRate2->getId())
            ->fillField('category[defaultMarkup]', '7.500')
            ->fillField('category[priceModel]', PriceModel::PRETTY_99->value)
            ->fillField('category[owner]', $owner2->getId())
            ->uncheckField('category[isActive]')
            ->click('Update Category')
            ->assertOn('/category/')
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
}
