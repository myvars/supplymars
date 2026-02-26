<?php

namespace App\Customer\Infrastructure\Mailer;

use App\Customer\Domain\Model\User\User;
use Symfony\Bridge\Twig\Mime\TemplatedEmail;
use Symfony\Component\Mailer\MailerInterface;
use Symfony\Component\Mime\Address;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

final readonly class MailerHelper
{
    public function __construct(
        private MailerInterface $mailer,
    ) {
    }

    /**
     * @param array<string, mixed> $emailSignatureContext
     */
    public function sendEmailVerificationMessage(User $user, array $emailSignatureContext): TemplatedEmail
    {
        $email = new TemplatedEmail()
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Please Confirm your Email')
            ->htmlTemplate('customer/registration/verify-email.html.twig')
            ->context($emailSignatureContext);

        $this->sendEmail($email);

        return $email;
    }

    public function sendAdminAccessGrantedMessage(User $user): TemplatedEmail
    {
        $email = new TemplatedEmail()
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('You now have admin access')
            ->htmlTemplate('customer/admin-access-granted.html.twig')
            ->context([
                'user' => $user,
            ]);

        $this->sendEmail($email);

        return $email;
    }

    public function sendEmailResetPasswordMessage(User $user, ResetPasswordToken $resetToken): TemplatedEmail
    {
        $email = new TemplatedEmail()
            ->to(new Address($user->getEmail(), $user->getFullName()))
            ->subject('Your password reset request')
            ->htmlTemplate('customer/reset_password/reset-password.html.twig')
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
