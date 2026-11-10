<?php

declare(strict_types=1);

namespace App\Tests\Home\Service;

use App\Home\Service\EngineeringLog;
use App\Home\Service\LogPost;
use PHPUnit\Framework\Attributes\DataProvider;
use PHPUnit\Framework\TestCase;

final class EngineeringLogTest extends TestCase
{
    private const string VALID = <<<'MD'
        ---
        title: A post
        period: March 2024
        summary: What happened.
        figures:
          - { value: '3', label: things }
        refs:
          - { label: ADR 001, url: 'https://example.com/adr' }
        ---

        ## A heading

        | A | B |
        |---|---|
        | 1 | 2 |

        <span onclick="alert(1)">raw</span>

        [A link](javascript:alert(1))
        MD;

    private string $directory;

    protected function setUp(): void
    {
        // Unique per test, so parallel processes never share it
        $this->directory = sys_get_temp_dir() . '/log-test-' . bin2hex(random_bytes(6));
        mkdir($this->directory);
    }

    protected function tearDown(): void
    {
        foreach (glob($this->directory . '/*') ?: [] as $file) {
            unlink($file);
        }

        rmdir($this->directory);
    }

    public function testEveryPublishedPostIsValid(): void
    {
        $posts = new EngineeringLog(\dirname(__DIR__, 3) . '/content/log')->all();

        self::assertNotEmpty($posts);
        foreach ($posts as $post) {
            self::assertNotSame('', $post->markdown, $post->slug);
        }
    }

    public function testReadsAPostNewestFirst(): void
    {
        $this->write('2024-03-31-older.md', self::VALID);
        $this->write('2024-09-30-newer.md', self::VALID);

        $posts = $this->log()->all();

        self::assertSame(['newer', 'older'], array_map(static fn (LogPost $post): string => $post->slug, $posts));
        self::assertSame('2024-09-30', $posts[0]->date->format('Y-m-d'));
        self::assertSame('A post', $posts[0]->title);
        self::assertSame('March 2024', $posts[0]->period);
        self::assertSame([['value' => '3', 'label' => 'things']], $posts[0]->figures);
        self::assertSame([['label' => 'ADR 001', 'url' => 'https://example.com/adr']], $posts[0]->refs);
    }

    public function testFindsBySlug(): void
    {
        $this->write('2024-03-31-a-post.md', self::VALID);

        self::assertSame('a-post', $this->log()->find('a-post')?->slug);
        self::assertNull($this->log()->find('missing'));
    }

    public function testAcceptsFigureKeysInAnyOrderAndNumbers(): void
    {
        $this->write('2024-03-31-a-post.md', str_replace("- { value: '3', label: things }", '- { label: things, value: 1.7 }', self::VALID));

        self::assertSame([['value' => '1.7', 'label' => 'things']], $this->log()->all()[0]->figures);
    }

    public function testRefusesTwoPostsWithTheSameSlug(): void
    {
        $this->write('2024-03-31-pricing.md', self::VALID);
        $this->write('2026-11-08-pricing.md', self::VALID);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageIsOrContains('share the slug "pricing"');

        $this->log()->all();
    }

    public function testNeighboursAreThePostsEitherSide(): void
    {
        $this->write('2024-03-31-first.md', self::VALID);
        $this->write('2024-09-30-second.md', self::VALID);
        $this->write('2025-01-31-third.md', self::VALID);
        $log = $this->log();

        $middle = $log->neighbours($this->found($log, 'second'));
        $newest = $log->neighbours($this->found($log, 'third'));
        $oldest = $log->neighbours($this->found($log, 'first'));

        self::assertSame(['third', 'first'], [$middle['newer']?->slug, $middle['older']?->slug]);
        self::assertSame([null, 'second'], [$newest['newer']?->slug, $newest['older']?->slug]);
        self::assertSame(['second', null], [$oldest['newer']?->slug, $oldest['older']?->slug]);
    }

    public function testRendersMarkdownWithTablesAndEscapesHtmlAndUnsafeLinks(): void
    {
        $this->write('2024-03-31-a-post.md', self::VALID);
        $log = $this->log();

        $html = $log->render($log->all()[0]);

        self::assertStringContainsString('<h2>A heading</h2>', $html);
        self::assertStringContainsString('<table>', $html);
        self::assertStringContainsString('&lt;span onclick', $html);
        self::assertStringNotContainsString('<span', $html);
        self::assertStringNotContainsString('javascript:', $html);
    }

    public function testALinkToAnotherPageOfTheSiteLeavesTheBodyFrame(): void
    {
        $this->write('2024-03-31-a-post.md', str_replace(
            '[A link](javascript:alert(1))',
            '[Another post](/log/another-post) and [GitHub](https://github.com)',
            self::VALID,
        ));
        $log = $this->log();

        $html = $log->render($log->all()[0]);

        self::assertStringContainsString('<a data-turbo-frame="_top" href="/log/another-post">', $html);
        self::assertStringContainsString('<a href="https://github.com">', $html);
    }

    /** @return iterable<string, array{string, string, string}> */
    public static function brokenPosts(): iterable
    {
        yield 'no date in the name' => ['a-post.md', self::VALID, 'must be named YYYY-MM-DD-slug.md'];
        yield 'capitals in the slug' => ['2024-03-31-A-Post.md', self::VALID, 'must be named YYYY-MM-DD-slug.md'];
        yield 'impossible date' => ['2024-02-30-a-post.md', self::VALID, 'has an invalid date'];
        yield 'no front matter' => ['2024-03-31-a-post.md', "Just text\n", 'has no front matter'];
        yield 'invalid YAML' => ['2024-03-31-a-post.md', "---\ntitle: [unclosed\n---\nBody\n", 'has invalid front matter'];
        yield 'no title' => ['2024-03-31-a-post.md', str_replace('title: A post', 'title: ""', self::VALID), 'needs a "title"'];
        yield 'no summary' => ['2024-03-31-a-post.md', str_replace("summary: What happened.\n", '', self::VALID), 'needs a "summary"'];
        yield 'figure without a label' => ['2024-03-31-a-post.md', str_replace('label: things', 'name: things', self::VALID), 'each of "figures" needs value and label'];
        yield 'reference that is not https' => ['2024-03-31-a-post.md', str_replace('https://example.com/adr', 'javascript:alert(1)', self::VALID), 'must link to an https:// URL'];
    }

    #[DataProvider('brokenPosts')]
    public function testRefusesABrokenPost(string $file, string $contents, string $message): void
    {
        $this->write($file, $contents);

        $this->expectException(\UnexpectedValueException::class);
        $this->expectExceptionMessageIsOrContains($message);

        $this->log()->all();
    }

    private function log(): EngineeringLog
    {
        return new EngineeringLog($this->directory);
    }

    private function found(EngineeringLog $log, string $slug): LogPost
    {
        $post = $log->find($slug);
        self::assertNotNull($post);

        return $post;
    }

    private function write(string $file, string $contents): void
    {
        file_put_contents($this->directory . '/' . $file, $contents);
    }
}
