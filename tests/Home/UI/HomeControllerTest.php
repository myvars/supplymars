<?php

declare(strict_types=1);

namespace App\Tests\Home\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Browser\Test\HasBrowser;

final class HomeControllerTest extends WebTestCase
{
    use HasBrowser;

    public function testShowHome(): void
    {
        $this->browser()
            ->get('/')
            ->assertSuccessful()
            ->assertSee('Welcome');
    }

    public function testShowHomeWhenAuthenticated(): void
    {
        $this->browser()
            ->actingAs(UserFactory::new()->asStaff()->create())
            ->get('/')
            ->assertSuccessful()
            ->assertSee('Welcome');
    }

    public function testShowAbout(): void
    {
        $this->browser()
            ->get('/about')
            ->assertSuccessful()
            ->assertSee('lifelong coder')
            ->assertSee('Ebuyer');
    }

    public function testShowStack(): void
    {
        $this->browser()
            ->get('/stack')
            ->assertSuccessful()
            ->assertSee('How SupplyMars Is Built')
            ->assertSee('A Modular Monolith')
            ->assertSee('Leaning on the Framework')
            ->assertSee('Merge Is Deploy');
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

    /**
     * CtaButton picks the link behaviour from the href: a path opens at the top, an http(s) URL in a new tab.
     * Labels and order are copy, so they are not pinned here.
     */
    public function testCallsToActionOpenWhereTheyShould(): void
    {
        foreach (['/', '/about', '/stack', '/log', '/contact', '/privacy', '/terms'] as $url) {
            $crawler = $this->browser()->get($url)->assertSuccessful()->crawler();

            self::assertCount(1, $crawler->filter('#closing-cta a[data-cta="primary"]'), $url . ': one primary button');

            $crawler->filter('a[data-cta]')->each(function (Crawler $link) use ($url): void {
                $href = (string) $link->attr('href');
                if (str_starts_with($href, 'http')) {
                    self::assertSame('_blank', $link->attr('target'), $url . ' ' . $href);
                    self::assertSame('noopener', $link->attr('rel'), $url . ' ' . $href);
                } elseif (str_starts_with($href, '/')) {
                    self::assertSame('_top', $link->attr('data-turbo-frame'), $url . ' ' . $href);
                }
            });
        }

        // The hero keeps its opt-out from Turbo prefetching the dashboard
        $this->browser()->get('/')
            ->assertElementAttributeContains('#hero a[href="/dashboard/"]', 'data-turbo-prefetch', 'false');
    }
}
