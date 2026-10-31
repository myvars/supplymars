<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI;

use App\Customer\Domain\Model\User\User;
use App\Tests\Shared\Factory\ResetPasswordRequestFactory;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Clock\Test\ClockSensitiveTrait;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Mime\Email;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;
use Zenstruck\Browser\Test\HasBrowser;

final class ResetPasswordFlowTest extends WebTestCase
{
    use ClockSensitiveTrait;
    use HasBrowser;

    public function testRequestingAResetQueuesTheEmail(): void
    {
        $this->requestReset();

        $transport = self::getContainer()->get('messenger.transport.async');
        self::assertInstanceOf(InMemoryTransport::class, $transport);

        $sent = $transport->getSent();
        self::assertCount(1, $sent);

        $message = $sent[0]->getMessage();
        self::assertInstanceOf(SendEmailMessage::class, $message);

        $email = $message->getMessage();
        self::assertInstanceOf(Email::class, $email);
        self::assertSame('Your password reset request', $email->getSubject());
    }

    public function testTheEmailIsSentFromTheConfiguredSender(): void
    {
        $this->requestReset();

        // The queued message is the original; the configured sender is applied to the logged copy.
        $event = self::getMailerEvent();
        self::assertNotNull($event);
        self::assertTrue($event->isQueued());
        self::assertEmailHeaderSame($event->getMessage(), 'From', 'supplymars <sender@test.example>');
        self::assertSame('sender@test.example', $event->getEnvelope()->getSender()->getAddress());
    }

    public function testTheLinkChangesThePassword(): void
    {
        $user = UserFactory::createOne(['email' => 'reset@example.com', 'password' => 'old-password']);
        $token = $this->resetToken($user);

        $this->browser()
            ->visit('/reset-password/reset/' . $token)
            ->assertOn('/reset-password/reset')
            ->fillField('New password', 'new-password')
            ->fillField('Confirm Password', 'new-password')
            ->click('Reset password')
            ->assertOn('/')
            ->assertSee('Your password has been updated.');

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user, 'new-password'));
        ResetPasswordRequestFactory::assert()->empty();
    }

    public function testAUsedLinkIsRefused(): void
    {
        $token = $this->resetToken(UserFactory::createOne());

        $this->browser()
            ->visit('/reset-password/reset/' . $token)
            ->fillField('New password', 'new-password')
            ->fillField('Confirm Password', 'new-password')
            ->click('Reset password')
            ->visit('/reset-password/reset/' . $token)
            ->assertOn('/reset-password')
            ->assertSee('The reset password link is invalid.');
    }

    public function testAnExpiredLinkIsRefused(): void
    {
        $user = UserFactory::createOne(['password' => 'old-password']);
        $token = $this->resetToken($user);

        // The bundle reads the time from Symfony's Clock; a link lasts an hour
        self::mockTime('+2 hours');

        $this->browser()
            ->visit('/reset-password/reset/' . $token)
            ->assertOn('/reset-password')
            ->assertSee('The link in your email is expired.');

        $hasher = self::getContainer()->get(UserPasswordHasherInterface::class);
        self::assertTrue($hasher->isPasswordValid($user, 'old-password'));
    }

    public function testTheResetPageWithoutATokenIsNotFound(): void
    {
        $this->browser()
            ->visit('/reset-password/reset')
            ->assertStatus(404);
    }

    private function resetToken(User $user): string
    {
        return self::getContainer()->get(ResetPasswordHelperInterface::class)->generateResetToken($user)->getToken();
    }

    private function requestReset(): void
    {
        UserFactory::createOne(['email' => 'reset@example.com']);

        // Redirects are not followed: the next request would start a new kernel and an empty transport.
        $this->browser()
            ->visit('/reset-password')
            ->interceptRedirects()
            ->fillField('Email', 'reset@example.com')
            ->click('Send Reset Link')
            ->assertRedirected()
            ->assertHeaderContains('Location', '/reset-password/check-email');
    }
}
