<?php

declare(strict_types=1);

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(UserDoctrineRepository::class)]
interface UserRepository extends FindByCriteriaInterface
{
    public function add(User $user): void;

    public function remove(User $user): void;

    public function get(UserId $id): ?User;

    public function getByPublicId(UserPublicId $publicId): ?User;

    public function getStaffById(UserId $id): ?User;

    public function getRandomUser(): ?User;

    public function findByApiToken(string $token): ?User;

    public function getByEmail(string $email): ?User;
}
