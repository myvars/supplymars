<?php

namespace App\Customer\Application\Handler;

use App\Customer\Application\Command\ResetPassword;
use App\Customer\Domain\Model\User\User;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use SymfonyCasts\Bundle\ResetPassword\Exception\ResetPasswordExceptionInterface;
use SymfonyCasts\Bundle\ResetPassword\ResetPasswordHelperInterface;

final readonly class ResetPasswordHandler
{
    public function __construct(
        private ResetPasswordHelperInterface $resetPasswordHelper,
        private UserPasswordHasherInterface $passwordHasher,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(ResetPassword $command): Result
    {
        try {
            $user = $this->resetPasswordHelper->validateTokenAndFetchUser($command->token);
            \assert($user instanceof User);
        } catch (ResetPasswordExceptionInterface) {
            return Result::fail('This reset link is invalid or has expired.');
        }

        $this->resetPasswordHelper->removeResetRequest($command->token);

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $command->plainPassword)
        );

        $this->flusher->flush();

        return Result::ok('Your password has been updated. Please login with your new details.');
    }
}
