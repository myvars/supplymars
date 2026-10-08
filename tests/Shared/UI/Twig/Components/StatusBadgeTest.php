<?php

namespace App\Tests\Shared\UI\Twig\Components;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

final class StatusBadgeTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testShowIconRendersTheIconForAStatusThatHasOne(): void
    {
        $badge = $this->renderTwigComponent('StatusBadge', ['status' => 'PENDING', 'showIcon' => true])->crawler()->filter('[data-slot="badge"]');

        self::assertSame('Pending', trim($badge->text()));
        self::assertCount(1, $badge->filter('svg'));
    }

    public function testShowIconRendersNoIconForAStatusWithoutOne(): void
    {
        $badge = $this->renderTwigComponent('StatusBadge', ['status' => 'OPEN', 'showIcon' => true])->crawler()->filter('[data-slot="badge"]');

        self::assertSame('Open', trim($badge->text()));
        self::assertCount(1, $badge->filter('.bg-blue-50'), 'keeps its status colour');
        self::assertCount(0, $badge->filter('svg'));
        self::assertCount(0, $badge->filter('div'));
    }

    public function testStatusIconDoesNotLeakItsTypeAsAnHtmlAttribute(): void
    {
        $icon = $this->renderTwigComponent('StatusIcon', ['type' => 'shipped', 'class' => 'me-1'])->crawler()->filter('div');

        self::assertNull($icon->attr('type'));
        self::assertSame('me-1', $icon->attr('class'));
        self::assertCount(1, $icon->filter('svg'));
    }
}
