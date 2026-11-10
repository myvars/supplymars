<?php

declare(strict_types=1);

namespace App\Home\Service;

use League\CommonMark\Environment\Environment;
use League\CommonMark\Extension\CommonMark\CommonMarkCoreExtension;
use League\CommonMark\Extension\CommonMark\Node\Inline\Link;
use League\CommonMark\Extension\DefaultAttributes\DefaultAttributesExtension;
use League\CommonMark\Extension\FrontMatter\Data\SymfonyYamlFrontMatterParser;
use League\CommonMark\Extension\FrontMatter\Exception\InvalidFrontMatterException;
use League\CommonMark\Extension\FrontMatter\FrontMatterParser;
use League\CommonMark\Extension\GithubFlavoredMarkdownExtension;
use League\CommonMark\MarkdownConverter;
use Symfony\Component\DependencyInjection\Attribute\Autowire;

/**
 * The engineering log's posts: one Markdown file per post in content/log/, named YYYY-MM-DD-slug.md, with front matter.
 * A file that does not match that shape throws, so a broken post fails the test suite rather than the page.
 */
final readonly class EngineeringLog
{
    private const string FILE_PATTERN = '/^(\d{4}-\d{2}-\d{2})-([a-z0-9]+(?:-[a-z0-9]+)*)\.md$/';

    private FrontMatterParser $frontMatterParser;

    private MarkdownConverter $converter;

    public function __construct(
        #[Autowire('%kernel.project_dir%/content/log')]
        private string $directory,
    ) {
        $this->frontMatterParser = new FrontMatterParser(new SymfonyYamlFrontMatterParser());
        // Posts are written in the repository, but escape raw HTML anyway: a post is Markdown only
        $environment = new Environment([
            'html_input' => 'escape',
            'allow_unsafe_links' => false,
            // A link to another page of the site is a full visit, which starts at the top. Followed inside the body
            // frame, the new page would open at the old one's scroll position
            'default_attributes' => [
                Link::class => [
                    'data-turbo-frame' => static fn (Link $link): ?string => str_starts_with($link->getUrl(), '/') ? '_top' : null,
                ],
            ],
        ]);
        $environment->addExtension(new CommonMarkCoreExtension());
        $environment->addExtension(new GithubFlavoredMarkdownExtension());
        $environment->addExtension(new DefaultAttributesExtension());
        $this->converter = new MarkdownConverter($environment);
    }

    /**
     * @return list<LogPost> newest first
     */
    public function all(): array
    {
        $files = glob($this->directory . '/*.md');
        $posts = array_map($this->read(...), $files === false ? [] : $files);

        $slugs = array_map(static fn (LogPost $post): string => $post->slug, $posts);
        $repeated = array_keys(array_filter(array_count_values($slugs), static fn (int $count): bool => $count > 1));
        if ($repeated !== []) {
            throw new \UnexpectedValueException(\sprintf('Log posts share the slug "%s"; each needs its own.', implode('", "', $repeated)));
        }

        // Newest first. glob() returns names sorted and usort() is stable, so posts on the same date keep slug order
        usort($posts, static fn (LogPost $a, LogPost $b): int => $b->date <=> $a->date);

        return $posts;
    }

    public function find(string $slug): ?LogPost
    {
        foreach ($this->all() as $post) {
            if ($post->slug === $slug) {
                return $post;
            }
        }

        return null;
    }

    /**
     * The posts either side of this one, to read the log in order.
     *
     * @return array{newer: ?LogPost, older: ?LogPost}
     */
    public function neighbours(LogPost $post): array
    {
        $posts = $this->all();
        $slugs = array_map(static fn (LogPost $each): string => $each->slug, $posts);
        $index = array_search($post->slug, $slugs, true);
        if ($index === false) {
            return ['newer' => null, 'older' => null];
        }

        return [
            'newer' => $posts[$index - 1] ?? null,
            'older' => $posts[$index + 1] ?? null,
        ];
    }

    public function render(LogPost $post): string
    {
        return $this->converter->convert($post->markdown)->getContent();
    }

    private function read(string $path): LogPost
    {
        $file = basename($path);
        if (preg_match(self::FILE_PATTERN, $file, $matches) !== 1) {
            throw new \UnexpectedValueException(\sprintf('Log post "%s" must be named YYYY-MM-DD-slug.md.', $file));
        }

        $date = \DateTimeImmutable::createFromFormat('!Y-m-d', $matches[1]);
        if ($date === false || $date->format('Y-m-d') !== $matches[1]) {
            throw new \UnexpectedValueException(\sprintf('Log post "%s" has an invalid date.', $file));
        }

        try {
            $document = $this->frontMatterParser->parse((string) file_get_contents($path));
        } catch (InvalidFrontMatterException $invalidFrontMatterException) {
            throw new \UnexpectedValueException(\sprintf('Log post "%s" has invalid front matter: %s', $file, $invalidFrontMatterException->getMessage()), $invalidFrontMatterException->getCode(), previous: $invalidFrontMatterException);
        }

        $meta = $document->getFrontMatter();
        if (!\is_array($meta)) {
            throw new \UnexpectedValueException(\sprintf('Log post "%s" has no front matter.', $file));
        }

        $figures = [];
        foreach ($this->pairs($meta, 'figures', 'value', 'label', $file) as [$value, $label]) {
            $figures[] = ['value' => $value, 'label' => $label];
        }

        $refs = [];
        foreach ($this->pairs($meta, 'refs', 'label', 'url', $file) as [$label, $url]) {
            if (!str_starts_with($url, 'https://')) {
                throw new \UnexpectedValueException(\sprintf('Log post "%s": reference "%s" must link to an https:// URL.', $file, $label));
            }

            $refs[] = ['label' => $label, 'url' => $url];
        }

        return new LogPost(
            slug: $matches[2],
            date: $date,
            title: $this->text($meta, 'title', $file),
            period: $this->text($meta, 'period', $file),
            summary: $this->text($meta, 'summary', $file),
            figures: $figures,
            refs: $refs,
            markdown: $document->getContent(),
        );
    }

    /**
     * @param array<mixed> $meta
     */
    private function text(array $meta, string $key, string $file): string
    {
        $value = $meta[$key] ?? null;
        if (!\is_string($value) || trim($value) === '') {
            throw new \UnexpectedValueException(\sprintf('Log post "%s" needs a "%s".', $file, $key));
        }

        return trim($value);
    }

    /**
     * An optional list of entries, each with exactly the two given keys, both text.
     *
     * @param array<mixed> $meta
     *
     * @return list<array{string, string}>
     */
    private function pairs(array $meta, string $key, string $first, string $second, string $file): array
    {
        $entries = $meta[$key] ?? [];
        if (!\is_array($entries) || !array_is_list($entries)) {
            throw new \UnexpectedValueException(\sprintf('Log post "%s": "%s" must be a list.', $file, $key));
        }

        $pairs = [];
        foreach ($entries as $entry) {
            if (!\is_array($entry) || \count($entry) !== 2 || !\array_key_exists($first, $entry) || !\array_key_exists($second, $entry)) {
                throw new \UnexpectedValueException(\sprintf('Log post "%s": each of "%s" needs %s and %s.', $file, $key, $first, $second));
            }

            $pair = [];
            foreach ([$first, $second] as $field) {
                if (!\is_string($entry[$field]) && !\is_int($entry[$field]) && !\is_float($entry[$field])) {
                    throw new \UnexpectedValueException(\sprintf('Log post "%s": "%s.%s" must be text.', $file, $key, $field));
                }

                $pair[] = (string) $entry[$field];
            }

            $pairs[] = [$pair[0], $pair[1]];
        }

        return $pairs;
    }
}
