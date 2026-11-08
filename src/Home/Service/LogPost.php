<?php

declare(strict_types=1);

namespace App\Home\Service;

/**
 * One engineering log post, read from a Markdown file in content/log/.
 */
final readonly class LogPost
{
    /**
     * @param list<array{value: string, label: string}> $figures
     * @param list<array{label: string, url: string}>   $refs
     */
    public function __construct(
        public string $slug,
        public \DateTimeImmutable $date,
        public string $title,
        public string $period,
        public string $summary,
        public array $figures,
        public array $refs,
        public string $markdown,
    ) {
    }
}
