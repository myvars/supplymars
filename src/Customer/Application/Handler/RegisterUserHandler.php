<?php

namespace App\Customer\Application\Handler;

use App\Customer\Application\Command\RegisterUser;
use App\Customer\Domain\Model\User\EmailVerifier;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Customer\Infrastructure\Mailer\MailerHelper;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class RegisterUserHandler
{
    public function __construct(
        private UserRepository $users,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
        private UserPasswordHasherInterface $passwordHasher,
        private EmailVerifier $emailVerifier,
        private MailerHelper $mailerHelper,
        private Security $security,
    ) {
    }

    public function __invoke(RegisterUser $command): Result
    {
        $user = User::create(
            fullName: $command->fullName,
            email: $command->email,
            isStaff: false,
            isVerified: false,
        );

        $user->setPassword(
            $this->passwordHasher->hashPassword($user, $command->plainPassword)
        );

        $errors = $this->validator->validate($user);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->users->add($user);
        $this->flusher->flush();

        $this->mailerHelper->sendEmailVerificationMessage(
            $user,
            $this->emailVerifier->createEmailSignatureContext('app_verify_email', $user),
        );

        $this->security->login($user);

        return Result::ok('Your account has been created. Follow the link in your email to verify your account.');
    }
}
