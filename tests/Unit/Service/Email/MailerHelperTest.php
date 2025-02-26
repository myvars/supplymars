<?php

namespace App\Tests\Unit\Service\Email;

use App\Entity\User;
use App\Service\Email\MailerHelper;
use PHPUnit\Framework\TestCase;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

class MailerHelperTest extends TestCase
{
    private MailerInterface $mailer;
    private MailerHelper $mailerHelper;

    protected function setUp(): void
    {
        $this->mailer = $this->createMock(MailerInterface::class);
        $this->mailerHelper = new MailerHelper($this->mailer);
    }

    public function testSendEmailVerificationMessage(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getFullName')->willReturn('Test User');

        $emailSignatureContext = ['signature' => 'Test Signature'];

        $this->mailer->expects($this->once())->method('send')->with($this->isInstanceOf(TemplatedEmail::class));

        $email = $this->mailerHelper->sendEmailVerificationMessage($user, $emailSignatureContext);

        $this->assertInstanceOf(TemplatedEmail::class, $email);
        $this->assertSame('test@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('Test User', $email->getTo()[0]->getName());
        $this->assertSame('Please Confirm your Email', $email->getSubject());
    }

    public function testSendEmailResetPasswordMessage(): void
    {
        $user = $this->createMock(User::class);
        $user->method('getEmail')->willReturn('test@example.com');
        $user->method('getFullName')->willReturn('Test User');

        $resetToken = $this->createMock(ResetPasswordToken::class);

        $this->mailer->expects($this->once())->method('send')->with($this->isInstanceOf(TemplatedEmail::class));

        $email = $this->mailerHelper->sendEmailResetPasswordMessage($user, $resetToken);

        $this->assertInstanceOf(TemplatedEmail::class, $email);
        $this->assertSame('test@example.com', $email->getTo()[0]->getAddress());
        $this->assertSame('Test User', $email->getTo()[0]->getName());
        $this->assertSame('Your password reset request', $email->getSubject());
    }
}