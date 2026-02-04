<?php

namespace App\Customer\Application\Handler;

use App\Customer\Application\Command\DeleteCustomer;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteCustomerHandler
{
    public function __construct(
        private UserRepository $customers,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteCustomer $command): Result
    {
        $customer = $this->customers->getByPublicId($command->id);
        if (!$customer instanceof User) {
            return Result::fail('Customer not found.');
        }

        if (!$customer->isDeletable()) {
            return Result::fail('Customer cannot be deleted.');
        }

        $this->customers->remove($customer);
        $this->flusher->flush();

        return Result::ok(message: 'Customer deleted');
    }
}
