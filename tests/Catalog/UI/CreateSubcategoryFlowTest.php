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

final class CreateSubcategoryFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSuccessfulCreationViaForm(): void
    {
        $category = CategoryFactory::new()->create();
        $owner = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/new')
            ->fillField('subcategory[category]', (string) $category->getId())
            ->fillField('subcategory[name]', 'Flow Subcategory')
            ->fillField('subcategory[defaultMarkup]', '5.000')
            ->fillField('subcategory[priceModel]', PriceModel::NONE->value)
            ->fillField('subcategory[owner]', (string) $owner->getId())
            ->fillField('subcategory[isActive]', '1')
            ->click('Create Subcategory')
            ->assertSuccessful()
            ->assertSee('Flow Subcategory')
            ->assertNotOn('/subcategory/');
    }

    public function testValidationErrorsOnEmptySubmission(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/new')
            ->click('Create Subcategory')
            ->assertOn('/subcategory/new')
            ->assertSee('Please choose a Category')
            ->assertSee('Please enter a subcategory name');
    }

    public function testRejectsOverLengthNameAsFieldError(): void
    {
        $category = CategoryFactory::new()->create();
        $owner = UserFactory::new()->asStaff()->create();

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/new')
            ->fillField('subcategory[category]', (string) $category->getId())
            ->fillField('subcategory[name]', str_repeat('a', 256))
            ->fillField('subcategory[defaultMarkup]', '5.000')
            ->fillField('subcategory[priceModel]', PriceModel::NONE->value)
            ->fillField('subcategory[owner]', (string) $owner->getId())
            ->fillField('subcategory[isActive]', '1')
            ->click('Create Subcategory')
            ->assertStatus(422)
            ->assertSeeIn('[data-slot="field-error"]', 'Max 255 characters')
            ->assertNotSee('Object(');

        SubcategoryFactory::assert()->empty();
    }
}
