<?php

namespace App\Tests\Shared\UI\Http;

use App\Shared\UI\Http\FlashMessenger;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Session\Session;
use Symfony\Component\HttpFoundation\Session\Storage\MockArraySessionStorage;

final class FlashMessengerTest extends TestCase
{
    /**
     * @return array{Request, Session}
     */
    private function makeRequestWithSession(): array
    {
        $session = new Session(new MockArraySessionStorage());
        $request = new Request();
        $request->setSession($session);

        return [$request, $session];
    }

    public function testSuccessAddsFlashWhenMessageProvided(): void
    {
        [$request, $session] = $this->makeRequestWithSession();

        $messenger = new FlashMessenger();
        $messenger->success($request, 'ok');

        self::assertSame(['ok'], $session->getFlashBag()->get('success'));
    }

    public function testSuccessDoesNothingOnNullMessage(): void
    {
        [$request, $session] = $this->makeRequestWithSession();

        $messenger = new FlashMessenger();
        $messenger->success($request, null);

        self::assertSame([], $session->getFlashBag()->get('success'));
    }

    public function testWarningAddsFlashWhenMessageProvided(): void
    {
        [$request, $session] = $this->makeRequestWithSession();

        $messenger = new FlashMessenger();
        $messenger->warning($request, 'heads up');

        self::assertSame(['heads up'], $session->getFlashBag()->get('warning'));
    }

    public function testWarningDoesNothingOnNullMessage(): void
    {
        [$request, $session] = $this->makeRequestWithSession();

        $messenger = new FlashMessenger();
        $messenger->warning($request, null);

        self::assertSame([], $session->getFlashBag()->get('warning'));
    }

    public function testErrorAddsFlashWhenMessageProvided(): void
    {
        [$request, $session] = $this->makeRequestWithSession();

        $messenger = new FlashMessenger();
        $messenger->error($request, 'bad');

        self::assertSame(['bad'], $session->getFlashBag()->get('danger'));
    }

    public function testErrorDoesNothingOnNullMessage(): void
    {
        [$request, $session] = $this->makeRequestWithSession();

        $messenger = new FlashMessenger();
        $messenger->error($request, null);

        self::assertSame([], $session->getFlashBag()->get('danger'));
    }
}
