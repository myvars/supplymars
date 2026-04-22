<?php

declare(strict_types=1);

namespace App\Reporting\Application\Handler\Report;

use App\Customer\Domain\Model\User\User;
use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use App\Shared\Application\Result;

final readonly class CustomerProfileInsightsHandler
{
    public function __construct(
        private UserDoctrineRepository $userRepository,
    ) {
    }

    public function __invoke(User $customer): Result
    {
        $insights = $this->userRepository->findCustomerInsights($customer);

        return Result::ok('Insights loaded', $insights);
    }
}
