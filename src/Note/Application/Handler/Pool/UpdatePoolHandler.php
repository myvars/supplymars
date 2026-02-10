<?php

namespace App\Note\Application\Handler\Pool;

use App\Note\Application\Command\Pool\UpdatePool;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Repository\PoolRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class UpdatePoolHandler
{
    public function __construct(
        private PoolRepository $pools,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(UpdatePool $command): Result
    {
        $pool = $this->pools->getByPublicId($command->id);
        if (!$pool instanceof Pool) {
            return Result::fail('Pool not found.');
        }

        $pool->update(
            name: $command->name,
            description: $command->description,
            isActive: $command->isActive,
            isCustomerVisible: $command->isCustomerVisible,
        );

        $errors = $this->validator->validate($pool);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->flusher->flush();

        return Result::ok('Pool updated');
    }
}
