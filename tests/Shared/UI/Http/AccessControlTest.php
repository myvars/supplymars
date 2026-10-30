<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use App\Tests\Shared\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * Who reaches what, as access_control in config/packages/security.yaml decides it: one page per
 * context, the API and the public pages. Audit has no row: its pages need a record and sit under
 * the same ^/ rule. A page's own tests log in as staff and test the page, and any refusal of its own.
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
        yield 'API docs' => ['/api/doc']; // its rule must stay above the one that closes ^/api/
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
}
