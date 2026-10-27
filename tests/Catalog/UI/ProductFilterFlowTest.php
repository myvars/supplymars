<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\SubcategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ProductFilterFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSubcategoryFieldOffersOnlyTheChosenCategorysSubcategories(): void
    {
        $category = CategoryFactory::createOne();
        SubcategoryFactory::createOne(['category' => $category, 'name' => 'Our Subcategory']);
        SubcategoryFactory::createOne(['category' => CategoryFactory::createOne(), 'name' => 'Other Subcategory']);

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/product/search/filter?categoryId=' . $category->getId())
            ->assertSuccessful();

        $options = $browser->crawler()
            ->filter('select[name="product_filter[subcategory]"] option')
            ->each(static fn ($option): string => trim($option->text()));

        self::assertSame(['Choose a Subcategory', 'Our Subcategory'], $options);
    }
}
