<?php

namespace App\Audit\Domain\Repository;

use App\Audit\Domain\Model\StatusChange\StatusChangeLog;
use App\Audit\Domain\Model\StatusChange\StatusChangeLogId;
use App\Audit\Domain\Model\StatusChange\StatusChangeLogPublicId;
use App\Audit\Infrastructure\Persistence\Doctrine\StatusChangeLogDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(StatusChangeLogDoctrineRepository::class)]
interface StatusChangeLogRepository
{
    public function add(StatusChangeLog $statusChangeLog): void;

    public function remove(StatusChangeLog $statusChangeLog): void;

    public function get(StatusChangeLogId $id): ?StatusChangeLog;

    public function getByPublicId(StatusChangeLogPublicId $publicId): ?StatusChangeLog;
}
