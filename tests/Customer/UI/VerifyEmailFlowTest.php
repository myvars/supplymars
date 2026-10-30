<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI;

use App\Customer\Infrastructure\Security\EmailVerifier;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Zenstruck\Browser\Test\HasBrowser;

final class VerifyEmailFlowTest extends WebTestCase
{
    use HasBrowser;

    public function testSignedLinkVerifiesTheUser(): void
    {
        $user = UserFactory::createOne(['isVerified' => false]);
        $signedUrl = $this->signedUrl($user->getId());

        $this->browser()
            ->get($signedUrl)
            ->assertOn('/')
            ->assertSee('Your account has been verified.');

        self::assertTrue(UserFactory::repository()->find($user->getId())?->isVerified());
    }

    public function testTamperedLinkLeavesTheUserUnverified(): void
    {
        $user = UserFactory::createOne(['isVerified' => false]);
        $signedUrl = $this->signedUrl($user->getId());

        $this->browser()
            ->get(str_replace('signature=', 'signature=x', $signedUrl))
            ->assertOn('/register');

        self::assertFalse(UserFactory::repository()->find($user->getId())?->isVerified());
    }

    private function signedUrl(?int $userId): string
    {
        $user = UserFactory::repository()->find($userId);
        self::assertNotNull($user);

        $context = self::getContainer()->get(EmailVerifier::class)->createEmailSignatureContext('app_verify_email', $user);
        self::assertIsString($context['signedUrl']);

        return $context['signedUrl'];
    }
}
