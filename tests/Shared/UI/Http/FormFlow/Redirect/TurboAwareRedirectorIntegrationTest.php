<?php

namespace App\Tests\Shared\UI\Http\FormFlow\Redirect;

use App\Shared\UI\Http\FormFlow\Redirect\TurboAwareRedirector;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\HttpFoundation\Request;

final class TurboAwareRedirectorIntegrationTest extends KernelTestCase
{
    private TurboAwareRedirector $redirector;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->redirector = self::getContainer()->get(TurboAwareRedirector::class);
    }

    public function testTurboRequestReturnsStreamWithRealTemplate(): void
    {
        $request = new Request();
        $request->headers->set('Accept', 'text/vnd.turbo-stream.html');

        $response = $this->redirector->to($request, '/target-url', refresh: true);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('turbo-stream', $response->getContent());
        self::assertStringContainsString('/target-url', $response->getContent());
    }

    public function testTurboRequestWithoutRefreshOmitsUrl(): void
    {
        $request = new Request();
        $request->headers->set('turbo-frame', 'modal');

        $response = $this->redirector->to($request, '/ignored-url', refresh: false);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('turbo-stream', $response->getContent());
        // URL should not appear when refresh=false
        self::assertStringNotContainsString('/ignored-url', $response->getContent());
    }

    public function testNonTurboRequestReturnsRedirectResponse(): void
    {
        $request = new Request();
        // No Turbo headers

        $response = $this->redirector->to($request, '/redirect-target', refresh: false, status: 303);

        self::assertSame(303, $response->getStatusCode());
        self::assertSame('/redirect-target', $response->headers->get('Location'));
    }

    public function testTurboFrameHeaderTriggersTurboResponse(): void
    {
        $request = new Request();
        $request->headers->set('Turbo-Frame', 'content-frame');

        $response = $this->redirector->to($request, '/frame-url', refresh: true);

        self::assertSame(200, $response->getStatusCode());
        self::assertStringContainsString('turbo-stream', $response->getContent());
    }
}
