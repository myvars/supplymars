<?php

namespace App\Tests\Shared\UI\Twig\Components;

use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\UX\TwigComponent\Test\InteractsWithTwigComponents;

final class FlashToastTest extends KernelTestCase
{
    use InteractsWithTwigComponents;

    public function testSuccessRendersAKitToastTheToasterCanPickUp(): void
    {
        $toast = $this->renderTwigComponent('FlashToast', ['type' => 'success', 'message' => 'Task saved'])->crawler()->filter('li');

        self::assertSame('toast', $toast->attr('data-slot'));
        self::assertSame('toast', $toast->attr('data-sonner-target'));
        self::assertSame('success', $toast->attr('data-type'));
        self::assertSame('3500', $toast->attr('data-duration'));
        self::assertSame('status', $toast->attr('role'));
        self::assertNotNull($toast->attr('data-turbo-temporary'));
        self::assertSame('Task saved', trim($toast->filter('[data-slot="toast-title"]')->text()));
        self::assertCount(1, $toast->filter('[data-slot="toast-icon"].bg-green-100 svg'));
    }

    public function testDangerMapsToAnAssertiveErrorToastThatStaysLonger(): void
    {
        $toast = $this->renderTwigComponent('FlashToast', ['type' => 'danger', 'message' => 'Could not save'])->crawler()->filter('li');

        self::assertSame('error', $toast->attr('data-type'));
        self::assertSame('alert', $toast->attr('role'));
        self::assertSame('assertive', $toast->attr('aria-live'));
        self::assertSame('6000', $toast->attr('data-duration'));
        self::assertCount(1, $toast->filter('[data-slot="toast-icon"].bg-red-100 svg'));
    }

    public function testMessageIsEscapedOnce(): void
    {
        $html = (string) $this->renderTwigComponent('FlashToast', ['type' => 'info', 'message' => 'Renamed "A & B" <draft>']);

        self::assertStringContainsString('Renamed &quot;A &amp; B&quot; &lt;draft&gt;', $html);
        self::assertStringNotContainsString('&amp;amp;', $html);
    }

    public function testUnknownTypeIsRejected(): void
    {
        $this->expectException(\Throwable::class);

        $this->renderTwigComponent('FlashToast', ['type' => 'nope', 'message' => 'x']);
    }
}
