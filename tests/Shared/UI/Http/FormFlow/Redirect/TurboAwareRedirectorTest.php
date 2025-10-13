<?php
namespace App\Tests\Shared\UI\Http\FormFlow\Redirect;

use App\Shared\UI\Http\FormFlow\Redirect\TurboAwareRedirector;
use PHPUnit\Framework\TestCase;
use Symfony\Component\HttpFoundation\RedirectResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Twig\Environment;

final class TurboAwareRedirectorTest extends TestCase
{
    private function twigMock(?string $renderResult, bool $throw = false): Environment
    {
        $twig = $this->createStub(Environment::class);
        if ($throw) {
            $twig->method('render')->willThrowException(new \RuntimeException('fail'));
        } else {
            $twig->method('render')->willReturn($renderResult);
        }
        return $twig;
    }

    public function testTurboDetectedByHeader(): void
    {
        $request = new Request();
        $request->headers->set('Turbo-Frame', 'frame-id');

        $redirector = new TurboAwareRedirector($this->twigMock('<turbo-stream/>'));

        $response = $redirector->to($request, '/target');
        self::assertInstanceOf(Response::class, $response);
        self::assertNotInstanceOf(RedirectResponse::class, $response);
        self::assertSame(200, $response->getStatusCode());
    }

    public function testTurboDetectedByAcceptHeader(): void
    {
        $request = new Request();
        $request->headers->set('Accept', 'text/vnd.turbo-stream.html');

        $redirector = new TurboAwareRedirector($this->twigMock('<stream/>'));

        $response = $redirector->to($request, '/x');
        self::assertSame(200, $response->getStatusCode());
    }

    public function testRefreshUrlPassedWhenRefreshTrue(): void
    {
        $request = new Request();
        $request->headers->set('turbo-frame', 'f');

        $redirector = new TurboAwareRedirector($this->twigMock('<stream/>'));

        $response = $redirector->to($request, '/new', true);
        self::assertStringContainsString('stream', $response->getContent());
    }

    public function testTwigFailureFallsBackToEmptyContent(): void
    {
        $request = new Request();
        $request->headers->set('turbo-frame', 'f');

        $redirector = new TurboAwareRedirector($this->twigMock(null, true));

        $response = $redirector->to($request, '/any');
        self::assertSame('', $response->getContent());
    }

    public function testNonTurboReturnsRedirect(): void
    {
        $request = new Request();

        $redirector = new TurboAwareRedirector($this->twigMock('<ignored/>'));

        $response = $redirector->to($request, '/dest', false, 302);
        self::assertInstanceOf(RedirectResponse::class, $response);
        self::assertSame(302, $response->getStatusCode());
        self::assertSame('/dest', $response->getTargetUrl());
    }
}
