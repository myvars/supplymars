<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Shared\Domain\ValueObject\PriceModel;
use App\Tests\Shared\Factory\CategoryFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\FilterRedirect;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class SubcategoryFilterFlowTest extends WebTestCase
{
    use FilterRedirect;
    use HasBrowser;

    public function testSubmitFilterFormRedirectsWithParams(): void
    {
        $category = CategoryFactory::new()->create();
        $manager = UserFactory::new()->asStaff()->create();

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/subcategory/search/filter')
            ->fillField('subcategory_filter[category]', (string) $category->getId())
            ->fillField('subcategory_filter[priceModel]', PriceModel::PRETTY_99->value)
            ->fillField('subcategory_filter[manager]', (string) $manager->getId())
            ->click('Apply Filter');

        $this->assertRedirectedWithQuery($browser, '/subcategory/', [
            'sort' => 'id',
            'sortDirection' => 'ASC',
            'page' => 1,
            'limit' => 5,
            'categoryId' => $category->getId(),
            'managerId' => $manager->getId(),
            'priceModel' => strtolower(PriceModel::PRETTY_99->value),
            'filter' => 'on',
        ]);
    }
}
