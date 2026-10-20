<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

/**
 * In production every request comes through the host Caddy, from a private address. framework.yaml
 * trusts its X-Forwarded-For and X-Forwarded-Proto, so the app sees the visitor's IP and HTTPS.
 *
 * Visitors use ordinary public addresses: private_ranges also covers the documentation ranges
 * (192.0.2.0/24, 198.51.100.0/24, 203.0.113.0/24), so an address from those would count as a proxy.
 */
class TrustedProxyTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    private const string PROXY = '172.18.0.5';

    private const string PASSWORD = 'correct-horse-battery';

    public function testLoginThrottlingCountsTheVisitorBehindTheProxyNotTheProxy(): void
    {
        UserFactory::new(['email' => 'staff@example.com', 'password' => self::PASSWORD])->asStaff()->create();
        $browser = $this->browser()->disableReboot();
        $browser->client()->followRedirects(false);

        // An attacker behind the proxy uses up the five failed attempts login_throttling allows
        $this->viaProxy($browser, '81.2.69.150');
        $token = $this->csrfToken($browser);
        for ($i = 1; $i <= 6; ++$i) {
            $browser->post('/login', ['body' => ['email' => 'staff@example.com', 'password' => 'wrong', '_csrf_token' => $token]]);
        }

        $browser->get('/login')->assertContains('Too many failed login attempts');

        // Through the same proxy, the real user is still let in, and sent on to an https URL
        $this->viaProxy($browser, '81.2.69.160');
        $token = $this->csrfToken($browser);
        $browser
            ->post('/login', ['body' => ['email' => 'staff@example.com', 'password' => self::PASSWORD, '_csrf_token' => $token]])
            ->assertStatus(302)
            ->assertHeaderContains('Location', 'https://localhost/');
    }

    public function testGeneratedUrlsUseHttpsWhenTheProxySaysSo(): void
    {
        $browser = $this->browser();
        $browser->client()->followRedirects(false);
        $this->viaProxy($browser, '81.2.69.170');
        // Only the client IP and the scheme are trusted: a forwarded host must not change generated links
        // (reset password emails would otherwise point wherever the request said)
        $browser->client()->setServerParameter('HTTP_X_FORWARDED_HOST', 'evil.example');
        $browser->client()->setServerParameter('HTTP_X_FORWARDED_PORT', '8443');

        $browser
            ->get('/dashboard/')
            ->assertStatus(302)
            ->assertHeaderEquals('Location', 'https://localhost/login');
    }

    public function testForwardedHeadersFromAnUntrustedAddressAreIgnored(): void
    {
        $browser = $this->browser()->disableReboot();
        $browser->client()->followRedirects(false);
        $browser->client()->setServerParameter('REMOTE_ADDR', '81.2.69.180');
        $browser->client()->setServerParameter('HTTP_X_FORWARDED_PROTO', 'https');

        $browser
            ->get('/dashboard/')
            ->assertHeaderEquals('Location', 'http://localhost/login');

        // A different X-Forwarded-For each time does not get round the /login limit
        for ($i = 1; $i <= 10; ++$i) {
            $browser->client()->setServerParameter('HTTP_X_FORWARDED_FOR', '81.2.69.' . $i);
            $browser->get('/login')->assertSuccessful();
        }

        $browser->client()->setServerParameter('HTTP_X_FORWARDED_FOR', '81.2.69.99');
        $browser->get('/login')->assertStatus(429);
    }

    private function viaProxy(KernelBrowser $browser, string $visitorIp): void
    {
        $browser->client()->setServerParameter('REMOTE_ADDR', self::PROXY);
        $browser->client()->setServerParameter('HTTP_X_FORWARDED_FOR', $visitorIp);
        $browser->client()->setServerParameter('HTTP_X_FORWARDED_PROTO', 'https');
        // The login form's CSRF check compares the Origin with the (https) request
        $browser->client()->setServerParameter('HTTP_ORIGIN', 'https://localhost');
    }

    private function csrfToken(KernelBrowser $browser): string
    {
        return (string) $browser->get('/login')->crawler()->filter('input[name="_csrf_token"]')->attr('value');
    }
}
