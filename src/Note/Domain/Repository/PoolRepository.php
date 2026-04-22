<?php

declare(strict_types=1);

namespace App\Note\Domain\Repository;

use App\Note\Domain\Model\Pool\Pool;
use App\Note\Domain\Model\Pool\PoolId;
use App\Note\Domain\Model\Pool\PoolPublicId;
use App\Note\Infrastructure\Persistence\Doctrine\PoolDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(PoolDoctrineRepository::class)]
interface PoolRepository extends FindByCriteriaInterface
{
    public function add(Pool $pool): void;

    public function remove(Pool $pool): void;

    public function get(PoolId $id): ?Pool;

    public function getByPublicId(PoolPublicId $publicId): ?Pool;

    /**
     * @return array<int, Pool>
     */
    public function findActive(): array;
}
