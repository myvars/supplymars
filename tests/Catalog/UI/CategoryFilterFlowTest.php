<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\UI\FilterRedirect;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class CategoryFilterFlowTest extends WebTestCase
{
    use FilterRedirect;
    use HasBrowser;

    public function testSubmitFilterFormRedirectsWithParams(): void
    {
        $manager = UserFactory::new()->asStaff()->create();
        $vat = VatRateFactory::createOne();

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/search/filter')
            ->fillField('category_filter[priceModel]', PriceModel::DEFAULT->value)
            ->fillField('category_filter[manager]', (string) $manager->getId())
            ->fillField('category_filter[vatRate]', (string) $vat->getId())
            ->click('Apply Filter');

        $this->assertRedirectedWithQuery($browser, '/category/', [
            'sort' => 'id',
            'sortDirection' => 'ASC',
            'page' => 1,
            'limit' => 5,
            'managerId' => $manager->getId(),
            'vatRateId' => $vat->getId(),
            'priceModel' => strtolower(PriceModel::DEFAULT->value),
            'filter' => 'on',
        ]);
    }
}
