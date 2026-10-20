<?php

declare(strict_types=1);

namespace App\Tests\Shared\UI\Http;

use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

/**
 * The rules in config/packages/rate_limiter.yaml. The counts are in memory in tests, so each test keeps
 * one kernel (disableReboot) for its counts to carry from request to request.
 */
class RateLimitTest extends WebTestCase
{
    use HasBrowser;

    public function testLoginIsRefusedAfterTenRequestsFromOneAddress(): void
    {
        $browser = $this->browser()->disableReboot();

        $this->from($browser, '81.2.69.110');
        for ($i = 1; $i <= 10; ++$i) {
            $browser->get('/login')->assertSuccessful();
        }

        $browser
            ->get('/login')
            ->assertStatus(429)
            ->assertContains('Too many requests');
        self::assertGreaterThan(0, (int) $browser->client()->getResponse()->headers->get('Retry-After'));

        // Another visitor has a count of their own
        $this->from($browser, '81.2.69.111');
        $browser->get('/login')->assertSuccessful();
    }

    public function testHealthIsRefusedAfterTenCallsFromOneAddressButNotForTheContainer(): void
    {
        $browser = $this->browser()->disableReboot();

        $this->from($browser, '81.2.69.120');
        for ($i = 1; $i <= 10; ++$i) {
            $browser->get('/health')->assertStatus(200);
        }

        $browser->get('/health')->assertStatus(429);

        // One caller using up its count does not lock out the uptime monitor
        $this->from($browser, '81.2.69.121');
        $browser->get('/health')->assertStatus(200);

        // The Docker healthcheck calls from inside the container, so it is never counted
        $this->from($browser, '127.0.0.1');
        for ($i = 1; $i <= 11; ++$i) {
            $browser->get('/health')->assertStatus(200);
        }
    }

    public function testBackupHealthIsRefusedAfterSixCallsFromOneAddress(): void
    {
        $browser = $this->browser()->disableReboot();

        // No backup in the test filesystem, so an allowed call gets 503; a refused one gets 429
        $this->from($browser, '81.2.69.140');
        for ($i = 1; $i <= 6; ++$i) {
            $browser->get('/health/backup')->assertStatus(503);
        }

        $browser->get('/health/backup')->assertStatus(429);

        $this->from($browser, '81.2.69.141');
        $browser->get('/health/backup')->assertStatus(503);
    }

    public function testOnlyFormPostsCountForTheSignUpAndPasswordRule(): void
    {
        $browser = $this->browser()->disableReboot();
        $browser->client()->followRedirects(false);
        $this->from($browser, '81.2.69.130');

        // Loading the form, or a Turbo prefetch of it, uses none of the allowance
        for ($i = 1; $i <= 5; ++$i) {
            $browser->get('/register')->assertStatus(200);
        }

        // A submit and two resubmissions get through; the next is refused
        for ($i = 1; $i <= 3; ++$i) {
            $browser->post('/register');
            self::assertNotSame(429, $browser->client()->getResponse()->getStatusCode());
        }

        $browser->post('/register')->assertStatus(429);

        // Password reset and verification email resend submissions share the same count
        $browser->post('/reset-password')->assertStatus(429);
        $browser->post('/verify/resend')->assertStatus(429);

        // Another visitor has a count of their own
        $this->from($browser, '81.2.69.131');
        $browser->post('/verify/resend');
        self::assertNotSame(429, $browser->client()->getResponse()->getStatusCode());
    }

    public function testAnEncodedPathCountsAsThePathItRoutesTo(): void
    {
        $browser = $this->browser()->disableReboot();
        $browser->client()->followRedirects(false);
        $this->from($browser, '81.2.69.190');

        // The router decodes the path, so /log%69n is the login page and shares its count
        for ($i = 1; $i <= 10; ++$i) {
            $browser->get('/log%69n')->assertSuccessful();
        }

        $browser->get('/login')->assertStatus(429);
        $browser->get('/log%69n')->assertStatus(429);

        for ($i = 1; $i <= 3; ++$i) {
            $browser->post('/regist%65r');
        }

        $browser->post('/regist%65r')->assertStatus(429);
    }

    private function from(KernelBrowser $browser, string $ip): void
    {
        $browser->client()->setServerParameter('REMOTE_ADDR', $ip);
    }
}
