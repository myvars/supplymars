<?php

declare(strict_types=1);

namespace App\Note\Application\Handler\Pool;

use App\Note\Application\Command\Pool\CreatePool;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Repository\PoolRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\RedirectTarget;
use App\Shared\Application\Result;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final readonly class CreatePoolHandler
{
    private const string ROUTE = 'app_note_pool_show';

    public function __construct(
        private PoolRepository $pools,
        private FlusherInterface $flusher,
        private ValidatorInterface $validator,
    ) {
    }

    public function __invoke(CreatePool $command): Result
    {
        $pool = Pool::create(
            name: $command->name,
            description: $command->description,
            isActive: $command->isActive,
            isCustomerVisible: $command->isCustomerVisible,
        );

        $errors = $this->validator->validate($pool);
        if (count($errors) > 0) {
            return Result::fail((string) $errors);
        }

        $this->pools->add($pool);
        $this->flusher->flush();

        return Result::ok(
            message: 'Pool created',
            payload: $pool->getPublicId(),
            redirect: new RedirectTarget(
                route: self::ROUTE,
                params: ['id' => $pool->getPublicId()->value()],
            ),
        );
    }
}
