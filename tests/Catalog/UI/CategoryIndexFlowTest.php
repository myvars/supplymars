<?php

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
}
