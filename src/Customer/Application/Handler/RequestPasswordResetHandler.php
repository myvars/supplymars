<?php

namespace App\Customer\Application\Handler;

use App\Customer\Application\Command\RequestPasswordReset;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Customer\Infrastructure\Mailer\MailerHelper;
use App\Shared\Application\Result;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final readonly class RequestPasswordResetHandler
{
    public function __construct(
        private UserRepository $users,
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private MailerHelper $mailerHelper,
    ) {
    }

    public function __invoke(RequestPasswordReset $command): Result
    {
        $user = $this->users->getByEmail($command->email);

        // Do not reveal whether a user account was found or not.
        if (!$user instanceof User) {
            return Result::ok();
        }

        try {
            $resetToken = $this->resetPasswordHelper->generateResetToken($user);
        } catch (ResetPasswordExceptionInterface) {
            return Result::ok();
        }

        $this->mailerHelper->sendEmailResetPasswordMessage($user, $resetToken);

        return Result::ok(payload: $resetToken);
    }
}
