<?php

namespace App\Tests\Catalog\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class SubcategoryFilterFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSubmitFilterFormRedirectsWithParams(): void
    {
        $category = CategoryFactory::new()->create();
        $manager = UserFactory::new()->asStaff()->create();

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/search/filter')
            ->fillField('subcategory_filter[category]', $category->getId())
            ->fillField('subcategory_filter[priceModel]', PriceModel::PRETTY_99->value)
            ->fillField('subcategory_filter[manager]', $manager->getId())
            ->click('Apply Filter');

        $uri = $browser->crawler()->getUri();
        $query = [];
        parse_str((string) parse_url($uri, PHP_URL_QUERY), $query);

        self::assertSame('id', $query['sort']);
        self::assertSame('ASC', $query['sortDirection']);
        self::assertSame('1', $query['page']);
        self::assertSame('5', $query['limit']);
        self::assertSame(strtolower(PriceModel::PRETTY_99->value), $query['priceModel']);
        self::assertSame((string) $category->getId(), (string) $query['categoryId']);
        self::assertSame((string) $manager->getId(), (string) $query['managerId']);
    }
}
