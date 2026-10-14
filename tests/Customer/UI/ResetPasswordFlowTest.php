<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI;

use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;
use Symfony\Component\Mailer\Messenger\SendEmailMessage;
use Symfony\Component\Messenger\Transport\InMemory\InMemoryTransport;
use Symfony\Component\Mime\Email;
use Zenstruck\Browser\Test\HasBrowser;
use Zenstruck\Foundry\Test\Factories;

final class ResetPasswordFlowTest extends WebTestCase
{
    use HasBrowser;
    use Factories;

    public function testRequestingAResetQueuesTheEmail(): void
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
}
