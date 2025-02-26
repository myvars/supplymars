<?php

namespace App\Tests\Application\Controller;

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
            ->get("/")
            ->assertSuccessful()
            ->assertSee('Welcome');
    }

    public function testShowAbout(): void
    {
        $this->browser()
            ->get("/about")
            ->assertSuccessful()
            ->assertSee('About');
    }

    public function testShowContact(): void
    {
        $this->browser()
            ->get("/about")
            ->assertSuccessful()
            ->assertSee('Contact');
    }

    public function testShowPrivacy(): void
    {
        $this->browser()
            ->get("/about")
            ->assertSuccessful()
            ->assertSee('Privacy');
    }

    public function testShowTerms(): void
    {
        $this->browser()
            ->get("/terms")
            ->assertSuccessful()
            ->assertSee('Terms');
    }
}