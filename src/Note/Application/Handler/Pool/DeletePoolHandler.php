<?php

namespace App\Note\Application\Handler\Pool;

use App\Note\Application\Command\Pool\DeletePool;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Repository\PoolRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;

final readonly class DeletePoolHandler
{
    public function __construct(
        private PoolRepository $pools,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(DeletePool $command): Result
    {
        $pool = $this->pools->getByPublicId($command->id);
        if (!$pool instanceof Pool) {
            return Result::fail('Pool not found.');
        }

        $this->pools->remove($pool);
        $this->flusher->flush();

        return Result::ok(message: 'Pool deleted');
    }
}
