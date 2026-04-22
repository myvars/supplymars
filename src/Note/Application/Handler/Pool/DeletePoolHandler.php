<?php

declare(strict_types=1);

namespace App\Note\Application\Handler\Pool;

use App\Note\Application\Command\Pool\DeletePool;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Repository\PoolRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use Symfony\Bundle\SecurityBundle\Security;

final readonly class DeletePoolHandler
{
    public function __construct(
        private PoolRepository $pools,
        private FlusherInterface $flusher,
        private Security $security,
    ) {
    }

    public function __invoke(DeletePool $command): Result
    {
        if (!$this->security->isGranted('ROLE_SUPER_ADMIN')) {
            return Result::fail('Deleting is disabled for this user.');
        }

        $pool = $this->pools->getByPublicId($command->id);
        if (!$pool instanceof Pool) {
            return Result::fail('Pool not found.');
        }

        $this->pools->remove($pool);
        $this->flusher->flush();

        return Result::ok(message: 'Pool deleted');
    }
}
