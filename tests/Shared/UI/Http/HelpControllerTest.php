<?php

namespace App\Tests\Shared\UI\Http;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class HelpControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testHelpPageLoadsForProductRoute(): void
    {
        $this->browser()
            ->get('/help?page=/product/')
            ->assertSuccessful()
            ->assertSee('Products');
    }

    public function testHelpDirectRouteLoadsContent(): void
    {
        $this->browser()
            ->get('/help/catalog/products')
            ->assertSuccessful()
            ->assertSee('Products');
    }

    public function testHelpFallbackForUnknownPage(): void
    {
        $this->browser()
            ->get('/help?page=/nonexistent')
            ->assertSuccessful()
            ->assertSee('No help is available');
    }

    public function testHelpIsAccessibleWithoutAuthentication(): void
    {
        $this->browser()
            ->get('/help/home/welcome')
            ->assertSuccessful()
            ->assertSee('Welcome');
    }

    public function testHelpIsAccessibleWhenAuthenticated(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/help/catalog/categories')
            ->assertSuccessful()
            ->assertSee('Categories');
    }

    public function testHelpLoginPageLoadsAccountAccess(): void
    {
        $this->browser()
            ->get('/help?page=/login')
            ->assertSuccessful()
            ->assertSee('Account');
    }

    public function testHelpRelatedLinksWork(): void
    {
        $this->browser()
            ->get('/help/catalog/products?from=home/welcome')
            ->assertSuccessful()
            ->assertSee('Products')
            ->assertSeeElement('a[aria-label="Back"]');
    }

    public function testHelpDirectRouteHandlesMissingTemplate(): void
    {
        $this->browser()
            ->get('/help/nonexistent/page')
            ->assertSuccessful()
            ->assertSee('No help is available');
    }
}
