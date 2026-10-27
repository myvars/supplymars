<?php

declare(strict_types=1);

namespace App\Customer\Application\Handler;

use App\Catalog\Domain\Repository\ProductRepository;
use App\Customer\Application\Command\DeleteCustomer;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\ResetPasswordRequestRepository;
use App\Customer\Domain\Repository\UserRepository;
use App\Note\Domain\Repository\TicketRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeleteCustomerHandler
{
    public function __construct(
        private UserRepository $customers,
        private TicketRepository $tickets,
        private ProductRepository $products,
        private ResetPasswordRequestRepository $resetPasswordRequests,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeleteCustomer $command): Result
    {
        $customer = $this->customers->getByPublicId($command->id);
        if (!$customer instanceof User) {
            return Result::fail('Customer not found.');
        }

        if (
            !$customer->isDeletable()
            || $this->tickets->existsForCustomer($customer)
            || $this->products->existsForOwner($customer)
        ) {
            return Result::fail('Customer cannot be deleted.');
        }

        $this->resetPasswordRequests->removeRequests($customer);
        $this->customers->remove($customer);
        $this->flusher->flush();

        return Result::ok(message: 'Customer deleted');
    }
}
