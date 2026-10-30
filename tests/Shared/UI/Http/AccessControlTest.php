<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use App\Tests\Shared\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * Who reaches what, as access_control in config/packages/security.yaml decides it: one page per
 * context, the API and the public pages. A page's own tests log in as staff and test the page.
 */
final class AccessControlTest extends WebTestCase
{
    use HasBrowser;

    /** @return iterable<string, array{string}> */
    public static function staffPages(): iterable
    {
        yield 'catalog' => ['/product/'];
        yield 'customer' => ['/customer/'];
        yield 'home' => ['/dashboard/'];
        yield 'note' => ['/note/ticket/'];
        yield 'order' => ['/order/'];
        yield 'pricing' => ['/vat-rate/'];
        yield 'purchasing' => ['/purchase/order/'];
        yield 'reporting' => ['/reports/customer/insights'];
        yield 'review' => ['/review/'];
    }

    /** @return iterable<string, array{string}> */
    public static function publicPages(): iterable
    {
        yield 'home page' => ['/'];
        yield 'login' => ['/login'];
        yield 'health' => ['/health'];
        yield 'catalog API' => ['/api/v1/catalog/products'];
    }

    #[DataProvider('staffPages')]
    public function testAnonymousIsSentToLogin(string $page): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get($page)
            ->assertRedirectedTo('/login');
    }

    #[DataProvider('staffPages')]
    public function testCustomerIsRefused(string $page): void
    {
        $this->browser()
            ->actingAs(UserFactory::createOne())
            ->get($page)
            ->assertStatus(403);
    }

    #[DataProvider('staffPages')]
    public function testStaffIsAllowed(string $page): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get($page)
            ->assertSuccessful();
    }

    #[DataProvider('publicPages')]
    public function testAnonymousReachesPublicPage(string $page): void
    {
        $this->browser()
            ->interceptRedirects()
            ->get($page)
            ->assertSuccessful();
    }

    public function testApiRefusesAnonymousCustomerAndAllowsStaff(): void
    {
        $this->browser()->get('/api/v1/orders')->assertStatus(401);

        $this->browser()
            ->actingAs(UserFactory::createOne(), 'api')
            ->get('/api/v1/orders')
            ->assertStatus(403);

        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create(), 'api')
            ->get('/api/v1/orders')
            ->assertSuccessful();
    }

    // Super-admin-only (Docs/08-security.md). The five super-admin deletes are refused in their
    // handlers with a flash, not here; their Delete*FlowTest has the staff case
    public function testStaffIsRefusedCreatingAnOrderByHand(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/order/new')
            ->assertStatus(403);

        $this->browser()
            ->actingAs(UserFactory::new()->asSuperAdmin()->create())
            ->get('/order/new')
            ->assertSuccessful();
    }
}
