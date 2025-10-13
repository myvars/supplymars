<?php

namespace App\Tests\Integration\Service\Email;

use App\Customer\Domain\Model\User\EmailVerifier;
use App\Customer\Infrastructure\Mailer\MailerHelper;
use tests\Shared\Factory\UserFactory;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;
use Zenstruck\Foundry\Test\Factories;

class MailerHelperIntegrationTest extends KernelTestCase
{
    use Factories;

    private MailerHelper $mailerHelper;

    private EmailVerifier $emailVerifier;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->mailerHelper = static::getContainer()->get(MailerHelper::class);
        $this->emailVerifier = static::getContainer()->get(EmailVerifier::class);
    }

    public function testSendEmailVerificationMessage(): void
    {
        $user = UserFactory::createOne();
        $emailSignatureContext = $this->emailVerifier->createEmailSignatureContext('app_verify_email', $user);

        $email = $this->mailerHelper->sendEmailVerificationMessage($user, $emailSignatureContext);

        $this->assertInstanceOf(TemplatedEmail::class, $email);
        $this->assertSame($user->getEmail(), $email->getTo()[0]->getAddress());
        $this->assertSame($user->getFullName(), $email->getTo()[0]->getName());
        $this->assertSame('Please Confirm your Email', $email->getSubject());
    }

    public function testSendEmailResetPasswordMessage(): void
    {
        $user = UserFactory::createOne();
        $resetToken = new ResetPasswordToken('token', new \DateTimeImmutable(), time());

        $email = $this->mailerHelper->sendEmailResetPasswordMessage($user, $resetToken);

        $this->assertInstanceOf(TemplatedEmail::class, $email);
        $this->assertSame($user->getEmail(), $email->getTo()[0]->getAddress());
        $this->assertSame($user->getFullName(), $email->getTo()[0]->getName());
        $this->assertSame('Your password reset request', $email->getSubject());
    }
}
