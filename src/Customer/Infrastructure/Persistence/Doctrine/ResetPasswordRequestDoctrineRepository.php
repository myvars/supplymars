<?php

namespace App\Customer\Infrastructure\Persistence\Doctrine;

use App\Customer\Domain\Model\User\ResetPasswordRequest;
use App\Customer\Domain\Model\User\ResetPasswordRequestId;
use App\Customer\Domain\Model\User\ResetPasswordRequestPublicId;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\ResetPasswordRequestRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use SymfonyCasts\Bundle\ResetPassword\Model\ResetPasswordRequestInterface;
use SymfonyCasts\Bundle\ResetPassword\Persistence\Repository\ResetPasswordRequestRepositoryTrait;
use SymfonyCasts\Bundle\ResetPassword\Persistence\ResetPasswordRequestRepositoryInterface;

/**
 * @extends ServiceEntityRepository<ResetPasswordRequest>
 *
 * @method ResetPasswordRequest|null find($id, $lockMode = null, $lockVersion = null)
 * @method ResetPasswordRequest|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method ResetPasswordRequest[]    findAll()
 * @method ResetPasswordRequest[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class ResetPasswordRequestDoctrineRepository extends ServiceEntityRepository implements ResetPasswordRequestRepositoryInterface, ResetPasswordRequestRepository
{
    use ResetPasswordRequestRepositoryTrait;

    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, ResetPasswordRequest::class);
    }

    public function add(ResetPasswordRequest $resetPasswordRequest): void
    {
        $this->getEntityManager()->persist($resetPasswordRequest);
    }

    public function remove(ResetPasswordRequest $resetPasswordRequest): void
    {
        $this->getEntityManager()->remove($resetPasswordRequest);
    }

    public function get(ResetPasswordRequestId $id): ?ResetPasswordRequest
    {
        return $this->find($id->value());
    }

    public function getByPublicId(ResetPasswordRequestPublicId $publicId): ?ResetPasswordRequest
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    public function createResetPasswordRequest(object $user, \DateTimeInterface $expiresAt, string $selector, string $hashedToken): ResetPasswordRequestInterface
    {
        \assert($user instanceof User);

        return new ResetPasswordRequest($user, $expiresAt, $selector, $hashedToken);
    }
}
