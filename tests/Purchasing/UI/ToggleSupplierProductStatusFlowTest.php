<?php

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ToggleSupplierProductStatusFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;
    use ActionFormToken;

    public function testSuccessfulToggle(): void
    {
        $product = ProductFactory::createOne();
        $supplierProduct = SupplierProductFactory::createOne([
            'isActive' => true,
            'product' => $product,
        ]);

        $stockUrl = '/pricing/' . $product->getPublicId()->value() . '/stock';
        $button = 'form[action="/supplier-product/' . $supplierProduct->getPublicId()->value() . '/status/toggle"] button';

        $browser = $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($stockUrl)
            ->click($button)
            ->assertOn($stockUrl)
            ->assertStatus(200);

        self::assertFalse($supplierProduct->isActive());

        $browser
            ->click($button)
            ->assertOn($stockUrl)
            ->assertStatus(200);

        self::assertTrue($supplierProduct->isActive());
    }

    public function testToggleIsNotReachableWithGet(): void
    {
        $supplierProduct = SupplierProductFactory::createOne([
            'isActive' => true,
            'product' => ProductFactory::createOne(),
        ]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier-product/' . $supplierProduct->getPublicId()->value() . '/status/toggle')
            ->assertStatus(405);

        self::assertTrue($supplierProduct->isActive());
    }

    public function testToggleRejectsAMissingTokenOrAnotherSourcesToken(): void
    {
        $product = ProductFactory::createOne();
        $other = SupplierProductFactory::createOne(['isActive' => true, 'product' => $product]);
        $supplierProduct = SupplierProductFactory::createOne(['isActive' => true, 'product' => $product]);
        $stockUrl = '/pricing/' . $product->getPublicId()->value() . '/stock';
        $url = '/supplier-product/' . $supplierProduct->getPublicId()->value() . '/status/toggle';

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, $stockUrl, '/supplier-product/' . $other->getPublicId()->value() . '/status/toggle');

        $browser
            ->post($url)
            ->assertOn($stockUrl)
            ->assertSee('Invalid CSRF token.')
            ->post($url, ['body' => ['_token' => $otherToken]])
            ->assertOn($stockUrl)
            ->assertSee('Invalid CSRF token.');

        self::assertTrue($supplierProduct->isActive());
    }
}
