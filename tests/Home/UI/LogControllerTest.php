<?php

declare(strict_types=1);

namespace App\Tests\Home\UI;

use App\Home\Service\EngineeringLog;
use App\Home\Service\LogPost;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * The log's pages, read against whatever posts content/log holds: no test names a post, so writing one never
 * breaks a test. Whether each post is valid is EngineeringLogTest's job.
 */
final class LogControllerTest extends WebTestCase
{
    use HasBrowser;

    public function testVisitorSeesEveryPostNewestFirst(): void
    {
        $posts = $this->posts();
        $newest = $posts[0];

        $browser = $this->browser()
            ->get('/log')
            ->assertSuccessful()
            ->assertSeeIn('h1', 'Building SupplyMars')
            // Grouped by year, newest first
            ->assertSeeIn('#log-year-' . $newest->date->format('Y') . ' + ol li:first-child h3', $newest->title)
            ->assertElementCount('ol li a[href^="/log/"]', \count($posts))
            ->assertElementAttributeContains('a[href="/log/' . $newest->slug . '"]', 'data-turbo-frame', '_top');

        foreach ($posts as $post) {
            $browser->assertSeeElement('a[href="/log/' . $post->slug . '"]');
        }
    }

    public function testVisitorReadsTheNewestPost(): void
    {
        [$newest, $older] = $this->posts();

        $browser = $this->browser()
            ->get('/log/' . $newest->slug)
            ->assertSuccessful()
            ->assertSeeIn('h1', $newest->title)
            ->assertSee($newest->period)
            // The newest post links to the one before it, and to nothing newer
            ->assertSeeIn('nav[aria-label="More posts"] a[rel="prev"]', $older->title)
            ->assertElementCount('nav[aria-label="More posts"] a[rel="next"]', 0)
            ->assertSeeElement('a[href="/log"]');

        foreach ($newest->refs as $ref) {
            $browser->assertSeeElement('a[href="' . $ref['url'] . '"]');
        }
    }

    public function testPostShowsItsFigures(): void
    {
        $post = array_find($this->posts(), static fn (LogPost $post): bool => $post->figures !== []);
        if (!$post instanceof LogPost) {
            self::markTestSkipped('No post has a figures band.');
        }

        $browser = $this->browser()
            ->get('/log/' . $post->slug)
            ->assertSuccessful();

        foreach ($post->figures as $figure) {
            $browser->assertSeeIn('dl', $figure['label']);
        }
    }

    public function testPostLinksToTheNewerAndOlderPosts(): void
    {
        [$newer, $post, $older] = $this->posts();

        $this->browser()
            ->get('/log/' . $post->slug)
            ->assertSuccessful()
            ->assertSeeIn('nav[aria-label="More posts"] a[rel="next"]', $newer->title)
            ->assertSeeIn('nav[aria-label="More posts"] a[rel="prev"]', $older->title)
            ->click($older->title)
            ->assertOn('/log/' . $older->slug)
            ->assertSeeIn('h1', $older->title);
    }

    public function testUnknownPostIsNotFound(): void
    {
        $this->browser()
            ->get('/log/no-such-post')
            ->assertStatus(404)
            // Not a slug the route accepts: still a 404 for a visitor, not the login page
            ->get('/log/No_Such_Post')
            ->assertStatus(404);
    }

    public function testLogIsLinkedFromTheFooterAndAboutPage(): void
    {
        $this->browser()
            ->get('/about')
            ->assertSuccessful()
            ->assertElementCount('footer a[href="/log"]', 1)
            ->assertSeeElement('main a[href="/log"]');
    }

    /**
     * @return list<LogPost> the published posts, newest first; the tests need at least three
     */
    private function posts(): array
    {
        $posts = new EngineeringLog(\dirname(__DIR__, 3) . '/content/log')->all();
        self::assertGreaterThanOrEqual(3, \count($posts), 'The log tests need at least three posts.');

        return $posts;
    }
}
