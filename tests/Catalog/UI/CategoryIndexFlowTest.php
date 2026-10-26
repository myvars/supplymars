<?php

declare(strict_types=1);

namespace App\Tests\Catalog\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class CategoryIndexFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testIndexPageRendersSortHeaders(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/category/')
            ->assertSuccessful()
            ->assertSee('Category Search')
            ->assertSee('Category')
            ->assertSee('Markup');
    }

    public function testIndexPagesSpellThePlurals(): void
    {
        $browser = $this->browser()->actingAs(UserFactory::new()->asStaff()->create());

        $browser->get('/category/')
            ->assertSeeIn('h1', 'Categories')
            ->assertSeeElement('input[placeholder="Search categories..."]')
            ->assertSee('No categories found')
            ->assertNotSee('Categorys');

        $browser->get('/subcategory/')
            ->assertSeeIn('h1', 'Subcategories')
            ->assertSeeElement('input[placeholder="Search subcategories..."]')
            ->assertSee('No subcategories found')
            ->assertNotSee('Subcategorys');
    }
}
