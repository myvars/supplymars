<?php

namespace App\Tests\Catalog\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class CategoryFilterFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testSubmitFilterFormRedirectsWithParams(): void
    {
        $manager = UserFactory::new()->asStaff()->create();
        $vat = VatRateFactory::createOne();

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/search/filter')
            ->fillField('category_filter[priceModel]', PriceModel::DEFAULT->value)
            ->fillField('category_filter[manager]', $manager->getId())
            ->fillField('category_filter[vatRate]', $vat->getId())
            ->click('Apply Filter');

        // Assert query parameters
        $uri = $browser->crawler()->getUri();
        $query = [];
        parse_str((string) parse_url((string) $uri, PHP_URL_QUERY), $query);

        self::assertSame('id', $query['sort']);
        self::assertSame('ASC', $query['sortDirection']);
        self::assertSame('1', $query['page']);
        self::assertSame('5', $query['limit']);
        self::assertSame(strtolower(PriceModel::DEFAULT->value), $query['priceModel']);
        self::assertSame((string) $manager->getId(), $query['managerId']);
        self::assertSame((string) $vat->getId(), $query['vatRateId']);
    }
}
