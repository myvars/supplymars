<?php

declare(strict_types=1);

namespace App\Tests\Home\UI;

use App\Tests\Shared\Factory\UserFactory;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\DomCrawler\Crawler;
use Zenstruck\Browser\Test\HasBrowser;

final class HomeControllerTest extends WebTestCase
{
    use HasBrowser;

    private const string GITHUB = 'https://github.com/myvars/supplymars';

    private const string EMAIL = 'mailto:contact@supplymars.com';

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

    public function testHeroLeadsToThePlatformTheStackAndTheDeveloper(): void
    {
        $crawler = $this->browser()->get('/')->assertSuccessful()->crawler();

        self::assertSame(
            ['Explore the Platform' => '/dashboard/', "How It's Built" => '/stack', 'About the Developer' => '/about'],
            $this->ctaLinks($crawler->filter('section')->first()),
        );
    }

    /**
     * Each public page ends with one primary call to action, then the main sections the visitor is not on.
     *
     * @param array<string, string> $expected label => href, in order
     */
    #[DataProvider('closingCallsToAction')]
    public function testPageClosesWithItsCallsToAction(string $url, array $expected): void
    {
        $crawler = $this->browser()->get($url)->assertSuccessful()->crawler();
        $closing = $crawler->filter('#closing-cta');

        self::assertSame($expected, $this->ctaLinks($closing));
        self::assertCount(1, $closing->filter('a[data-slot="button"].bg-orange-500'), 'One primary button');

        $closing->filter('a[data-slot="button"]')->each(function (Crawler $link): void {
            $href = (string) $link->attr('href');
            if (str_starts_with($href, 'http')) {
                self::assertSame('_blank', $link->attr('target'), $href);
                self::assertSame('noopener', $link->attr('rel'), $href);
            } elseif (str_starts_with($href, '/')) {
                self::assertSame('_top', $link->attr('data-turbo-frame'), $href);
            }
        });
    }

    /**
     * @return iterable<string, array{string, array<string, string>}>
     */
    public static function closingCallsToAction(): iterable
    {
        yield 'home' => ['/', ['View on GitHub' => self::GITHUB, 'Engineering Log' => '/log', 'About the Developer' => '/about']];
        yield 'about' => ['/about', ['Get in Touch' => self::EMAIL, "How It's Built" => '/stack', 'Engineering Log' => '/log']];
        yield 'stack' => ['/stack', ['View on GitHub' => self::GITHUB, 'Engineering Log' => '/log', 'About the Developer' => '/about']];
        yield 'log' => ['/log', ['View on GitHub' => self::GITHUB, "How It's Built" => '/stack', 'About the Developer' => '/about']];
        yield 'contact' => ['/contact', ['Get in Touch' => self::EMAIL, 'About the Developer' => '/about', 'View on GitHub' => self::GITHUB]];
        yield 'privacy' => ['/privacy', ['Get in Touch' => self::EMAIL, 'Terms & Conditions' => '/terms']];
        yield 'terms' => ['/terms', ['Get in Touch' => self::EMAIL, 'Privacy Policy' => '/privacy']];
    }

    /**
     * @return array<string, string> label => href
     */
    private function ctaLinks(Crawler $section): array
    {
        $links = [];
        $section->filter('a[data-slot="button"]')->each(function (Crawler $link) use (&$links): void {
            $links[trim($link->text())] = (string) $link->attr('href');
        });

        return $links;
    }
}
