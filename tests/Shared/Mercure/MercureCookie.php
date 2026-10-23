<?php

declare(strict_types=1);

namespace App\Tests\Shared\Mercure;

use PHPUnit\Framework\Assert;
use Symfony\Component\HttpFoundation\Cookie;
use Zenstruck\Browser\KernelBrowser;

/** Reads the subscriber cookie that base.html.twig sets, and what its token grants. */
final class MercureCookie
{
    public const string NAME = '__Secure-mercure_access_token';

    /** The cookie the last response set, or null. */
    public static function find(KernelBrowser $browser): ?Cookie
    {
        foreach ($browser->client()->getResponse()->headers->getCookies() as $cookie) {
            if ($cookie->getName() === self::NAME) {
                return $cookie;
            }
        }

        return null;
    }

    /**
     * The topics the last response's cookie grants, by action (Mercure 1.0's authorization_details claim).
     *
     * @return array<string, list<string>>
     */
    public static function grantedTopics(KernelBrowser $browser): array
    {
        $cookie = self::find($browser);
        Assert::assertNotNull($cookie, 'No Mercure cookie was set');

        $payload = json_decode((string) base64_decode(strtr(explode('.', (string) $cookie->getValue())[1], '-_', '+/'), true), true, 512, \JSON_THROW_ON_ERROR);
        Assert::assertIsArray($payload);
        Assert::assertIsArray($payload['authorization_details'] ?? null);

        $granted = [];
        foreach ($payload['authorization_details'] as $detail) {
            foreach ($detail['actions'] as $action) {
                foreach ($detail['topics'] as $topic) {
                    $granted[$action][] = $topic['match'];
                }
            }
        }

        return $granted;
    }
}
