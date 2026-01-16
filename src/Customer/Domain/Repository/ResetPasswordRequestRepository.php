<?php

namespace App\Customer\Domain\Repository;

use App\Customer\Domain\Model\User\ResetPasswordRequest;
use App\Customer\Domain\Model\User\ResetPasswordRequestId;
use App\Customer\Domain\Model\User\ResetPasswordRequestPublicId;
use App\Customer\Infrastructure\Persistence\Doctrine\ResetPasswordRequestDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(ResetPasswordRequestDoctrineRepository::class)]
interface ResetPasswordRequestRepository
{
    public function add(ResetPasswordRequest $resetPasswordRequest): void;

    public function remove(ResetPasswordRequest $resetPasswordRequest): void;

    public function get(ResetPasswordRequestId $id): ?ResetPasswordRequest;

    public function getByPublicId(ResetPasswordRequestPublicId $publicId): ?ResetPasswordRequest;
}
