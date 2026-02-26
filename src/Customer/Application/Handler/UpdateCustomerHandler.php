<?php

namespace App\Customer\Application\Handler;

use App\Customer\Application\Command\UpdateCustomer;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Customer\Infrastructure\Mailer\MailerHelper;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdateCustomerHandler
{
    public function __construct(
        private UserRepository $customers,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
        private MailerHelper $mailerHelper,
    ) {
    }

    public function __invoke(UpdateCustomer $command): Result
    {
        $customer = $this->customers->getByPublicId($command->id);
        if (!$customer instanceof User) {
            return Result::fail('Customer not found.');
        }

        $wasStaff = $customer->isStaff();

        $customer->update(
            fullName: $command->fullName,
            email: $command->email,
            isStaff: $command->isStaff,
            isVerified: $command->isVerified,
        );

        $errors = $this->validator->validate($customer);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        if (!$wasStaff && $command->isStaff) {
            $this->mailerHelper->sendAdminAccessGrantedMessage($customer);
        }

        return Result::ok('Customer updated.', $customer->getPublicId());
    }
}
