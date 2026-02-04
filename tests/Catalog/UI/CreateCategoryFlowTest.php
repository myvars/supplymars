<?php

namespace App\Tests\Catalog\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class CreateCategoryFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulCreationViaForm(): void
    {
        $owner = UserFactory::new()->asStaff()->create();
        $vatRate = VatRateFactory::createOne();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/new')
            ->fillField('category[name]', 'Flow Category')
            ->fillField('category[vatRate]', (string) $vatRate->getId())
            ->fillField('category[defaultMarkup]', '5.000')
            ->fillField('category[priceModel]', PriceModel::DEFAULT->value)
            ->fillField('category[owner]', (string) $owner->getId())
            ->fillField('category[isActive]', '1')
            ->click('Create Category')
            ->assertSuccessful()
            ->assertSee('Flow Category')
            ->assertNotOn('/category/');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/new')
            ->click('Create Category')
            ->assertOn('/category/new')
            ->assertSee('Please enter a category name')
            ->assertSee('Please choose a VAT rate')
            ->assertSee('Please choose a category owner');
    }
}
