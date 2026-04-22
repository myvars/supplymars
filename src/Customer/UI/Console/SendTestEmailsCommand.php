<?php

declare(strict_types=1);

namespace App\Customer\UI\Console;

use App\Customer\Domain\Model\User\User;
use App\Customer\Infrastructure\Mailer\MailerHelper;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\When;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordToken;

#[When('dev')]
#[AsCommand(
    name: 'app:send-test-emails',
    description: 'Send all customer email templates to Mailpit for preview.',
)]
final readonly class SendTestEmailsCommand
{
    public function __construct(
        private MailerHelper $mailerHelper,
    ) {
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        $user = User::create(
            fullName: 'Jane Doe',
            email: 'test@example.com',
            isStaff: false,
            isVerified: false,
        );

        $this->mailerHelper->sendEmailVerificationMessage($user, [
            'signedUrl' => 'https://example.com/verify?token=test123',
            'expiresAtMessageKey' => '%count% hour|%count% hours',
            'expiresAtMessageData' => ['%count%' => 1],
        ]);
        $io->success('Sent: Verify Email');

        $resetToken = new ResetPasswordToken(
            'test-token-123',
            new \DateTimeImmutable('+1 hour'),
            time(),
        );
        $this->mailerHelper->sendEmailResetPasswordMessage($user, $resetToken);
        $io->success('Sent: Reset Password');

        $this->mailerHelper->sendAdminAccessGrantedMessage($user);
        $io->success('Sent: Admin Access Granted');

        $io->info('Check Mailpit at http://127.0.0.1:8025');

        return Command::SUCCESS;
    }
}
