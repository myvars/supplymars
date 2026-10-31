<?php

declare(strict_types=1);

namespace App\Tests\Purchasing\UI;

use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\UserFactory;
use App\Tests\Shared\Factory\VatRateFactory;
use App\Tests\Shared\UI\ActionFormToken;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class MapSupplierProductFlowTest extends WebTestCase
{
    use HasBrowser;
    use ActionFormToken;

    protected function setUp(): void
    {
        VatRateFactory::new()->withStandardRate()->create();
    }

    public function testSuccessfulMap(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => null,
        ]);

        $publicId = $supplierProduct->getPublicId()->value();

        $this->browser()
            ->actingAs($user)
            ->get('/supplier-product/' . $publicId)
            ->click('Map Product')
            ->assertOn('/supplier-product/' . $publicId)
            ->assertSee('Supplier product mapped');

        // The mapping creates the catalog product from the supplier product
        self::assertSame($supplierProduct->getName(), $supplierProduct->getProduct()?->getName());
    }

    public function testMapFailsWhenAlreadyMapped(): void
    {
        $user = UserFactory::new()->asStaff()->create();
        $supplierProduct = SupplierProductFactory::createOne([
            'product' => null,
        ]);

        $publicId = $supplierProduct->getPublicId()->value();

        $browser = $this->browser()->actingAs($user);
        $token = $this->actionToken($browser, '/supplier-product/' . $publicId, '/supplier-product/' . $publicId . '/map');

        $browser
            ->click('Map Product')
            ->assertSee('Supplier product mapped')
            ->post('/supplier-product/' . $publicId . '/map', ['body' => ['_token' => $token]])
            ->assertOn('/supplier-product/' . $publicId)
            ->assertSee('Supplier product already mapped');
    }

    public function testMapIsNotReachableWithGet(): void
    {
        $supplierProduct = SupplierProductFactory::createOne(['product' => null]);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/supplier-product/' . $supplierProduct->getPublicId()->value() . '/map')
            ->assertStatus(405);

        self::assertNull($supplierProduct->getProduct());
    }

    public function testMapRejectsAMissingTokenOrAnotherSupplierProductsToken(): void
    {
        $other = SupplierProductFactory::createOne(['product' => null])->getPublicId()->value();
        $supplierProduct = SupplierProductFactory::createOne(['product' => null]);
        $url = '/supplier-product/' . $supplierProduct->getPublicId()->value();

        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());
        $otherToken = $this->actionToken($browser, '/supplier-product/' . $other, '/supplier-product/' . $other . '/map');

        $browser
            ->post($url . '/map')
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.')
            ->post($url . '/map', ['body' => ['_token' => $otherToken]])
            ->assertOn($url)
            ->assertSee('Invalid CSRF token.');

        self::assertNull($supplierProduct->getProduct());
    }
}
