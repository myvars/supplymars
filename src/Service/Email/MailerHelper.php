<?php

namespace App\Service\Email;

use App\Entity\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

readonly class MailerHelper
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    public function sendEmailVerificationMessage(User $user, array $emailSignatureContext): TemplatedEmail
    {
        $email = new TemplatedEmail()
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Please Confirm your Email')
            ->htmlTemplate('email/verify-email.html.twig')
            ->context($emailSignatureContext);

        $this->sendEmail($email);

        return $email;
    }

    public function sendEmailResetPasswordMessage(User $user, ResetPasswordToken $resetToken): TemplatedEmail
    {
        $email = new TemplatedEmail()
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Your password reset request')
            ->htmlTemplate('email/reset-password.html.twig')
            ->context([
                'resetToken' => $resetToken,
            ]);

        $this->sendEmail($email);

        return $email;
    }

    private function sendEmail(TemplatedEmail $email): void
    {
        $this->mailer->send($email);
    }
}
