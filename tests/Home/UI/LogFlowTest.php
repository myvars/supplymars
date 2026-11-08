<?php

declare(strict_types=1);

namespace App\Tests\Home\UI;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class LogFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testVisitorSeesThePosts(): void
    {
        $this->browser()
            ->get('/log')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'Building SupplyMars')
            ->assertSee('Five weeks, 119 pull requests')
            ->assertSee('6 October – 8 November 2026')
            ->assertElementAttributeContains('a[href="/log/five-weeks-119-pull-requests"]', 'data-turbo-frame', 'body');
    }

    public function testVisitorReadsAPost(): void
    {
        $this->browser()
            ->get('/log/five-weeks-119-pull-requests')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'Five weeks, 119 pull requests')
            // A figure from the front matter, a heading and a table from the Markdown, and a reference
            ->assertSeeIn('dl', 'pull requests merged')
            ->assertSeeIn('article h2', 'How the weeks went')
            ->assertSee('When things went wrong')
            ->assertSeeIn('article table', '6–12 Oct')
            ->assertElementCount('a[href="https://github.com/myvars/supplymars/blob/main/Docs/adr/016-frankenphp-in-place-of-nginx-and-php-fpm.md"]', 1);
    }

    public function testUnknownPostIsNotFound(): void
    {
        $this->browser()
            ->get('/log/no-such-post')
            ->assertStatus(404);
    }

    public function testPostIsLinkedFromTheFooterAndAboutPage(): void
    {
        $this->browser()
            ->get('/about')
            ->assertSuccessful()
            ->assertElementCount('footer a[href="/log"]', 1)
            ->assertSeeElement('main a[href="/log"]');
    }
}
