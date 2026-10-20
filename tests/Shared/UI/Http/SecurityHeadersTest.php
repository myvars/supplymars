<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * The app sends the security headers, so dev and tests use the production policy. HSTS is the web
 * server's (docker/nginx/conf.d/prod.conf) and is not checked here.
 */
class SecurityHeadersTest extends WebTestCase
{
    use HasBrowser;

    private const string CSP = "default-src 'self'; base-uri 'self'; connect-src 'self' https://challenges.cloudflare.com; "
        . "form-action 'self'; frame-src 'self' https://challenges.cloudflare.com; "
        . "img-src 'self' data: https://unicorn-bucket-two.s3.eu-west-2.amazonaws.com; "
        . "script-src 'self' 'unsafe-inline' data: https://challenges.cloudflare.com; style-src 'self' 'unsafe-inline'";

    public function testPageSendsTheSecurityHeaders(): void
    {
        $browser = $this->browser()
            ->get('/login')
            ->assertSuccessful();

        $this->assertSecurityHeaders($browser);
    }

    public function testNotFoundPageSendsTheSecurityHeaders(): void
    {
        // In debug, Symfony drops the CSP from its exception page. Production renders errors without debug.
        $browser = $this->browser(['debug' => false])
            ->get('/no-such-page')
            ->assertStatus(404);

        $this->assertSecurityHeaders($browser);
    }

    private function assertSecurityHeaders(KernelBrowser $browser): void
    {
        $browser
            ->assertHeaderEquals('Content-Security-Policy', self::CSP)
            ->assertHeaderEquals('X-Frame-Options', 'SAMEORIGIN')
            ->assertHeaderEquals('X-Content-Type-Options', 'nosniff')
            ->assertHeaderEquals('Referrer-Policy', 'strict-origin-when-cross-origin')
            ->assertHeaderEquals('X-Content-Security-Policy', null);
    }
}
