<?php

declare(strict_types=1);

namespace App\Note\Application\Handler\Pool;

use App\Note\Application\Command\Pool\TogglePoolSubscription;
use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Repository\PoolRepository;
use App\Shared\Application\FlusherInterface;
use App\Shared\Application\Result;
use App\Shared\Infrastructure\Security\CurrentUserProvider;

final readonly class TogglePoolSubscriptionHandler
{
    public function __construct(
        private PoolRepository $pools,
        private FlusherInterface $flusher,
        private CurrentUserProvider $userProvider,
    ) {
    }

    public function __invoke(TogglePoolSubscription $command): Result
    {
        $pool = $this->pools->getByPublicId($command->poolId);
        if (!$pool instanceof Pool) {
            return Result::fail('Pool not found.');
        }

        $user = $this->userProvider->get();

        $wasSubscribed = $pool->isSubscribedBy($user);

        if ($wasSubscribed) {
            $pool->unsubscribe($user);
        } else {
            $pool->subscribe($user);
        }

        $this->flusher->flush();

        return Result::ok($wasSubscribed
            ? 'Unsubscribed from ' . $pool->getName()
            : 'Subscribed to ' . $pool->getName()
        );
    }
}
