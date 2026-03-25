<?php

namespace App\Tests\Home\UI;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

class HomeControllerTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testShowHome(): void
    {
        $this->browser()
            ->get('/')
            ->assertSuccessful()
            ->assertSee('Welcome');
    }

    public function testShowAbout(): void
    {
        $this->browser()
            ->get('/about')
            ->assertSuccessful()
            ->assertSee('The Developer')
            ->assertSee('Ebuyer');
    }

    public function testShowContact(): void
    {
        $this->browser()
            ->get('/contact')
            ->assertSuccessful()
            ->assertSee('Get in Touch')
            ->assertSee('story behind it');
    }

    public function testShowPrivacy(): void
    {
        $this->browser()
            ->get('/privacy')
            ->assertSuccessful()
            ->assertSee('Privacy');
    }

    public function testShowTerms(): void
    {
        $this->browser()
            ->get('/terms')
            ->assertSuccessful()
            ->assertSee('Terms');
    }
}
