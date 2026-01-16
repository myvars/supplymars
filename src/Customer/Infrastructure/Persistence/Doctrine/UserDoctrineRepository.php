<?php

namespace App\Customer\Infrastructure\Persistence\Doctrine;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\Domain\Repository\UserRepository;
use App\Shared\Application\Search\SearchCriteriaInterface;
use App\Shared\Infrastructure\Persistence\Search\FindByCriteriaInterface;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;
use Pagerfanta\Adapter\AdapterInterface;
use Pagerfanta\Doctrine\ORM\QueryAdapter;
use Symfony\Component\Security\Core\Exception\UnsupportedUserException;
use Symfony\Component\Security\Core\User\PasswordAuthenticatedUserInterface;
use Symfony\Component\Security\Core\User\PasswordUpgraderInterface;

/**
 * @extends ServiceEntityRepository<User>
 *
 * @implements PasswordUpgraderInterface<User>
 *
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array $criteria, array $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array $criteria, array $orderBy = null, $limit = null, $offset = null)
 */
class UserDoctrineRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, FindByCriteriaInterface, UserRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
    }

    public function add(User $user): void
    {
        $this->getEntityManager()->persist($user);
    }

    public function remove(User $user): void
    {
        $this->getEntityManager()->remove($user);
    }

    public function get(UserId $id): ?User
    {
        return $this->find($id->value());
    }

    public function getByPublicId(UserPublicId $publicId): ?User
    {
        return $this->findOneBy(['publicId' => $publicId->value()]);
    }

    /**
     * Used to upgrade (rehash) the user's password automatically over time.
     */
    public function upgradePassword(PasswordAuthenticatedUserInterface $user, string $newHashedPassword): void
    {
        if (!$user instanceof User) {
            throw new UnsupportedUserException(sprintf('Instances of "%s" are not supported.', $user::class));
        }

        $user->setPassword($newHashedPassword);
        $this->getEntityManager()->persist($user);
        $this->getEntityManager()->flush();
    }

    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        $sort = $criteria->getSort();
        $sortDirection = $criteria->getSortDirection();

        $qb = $this->createQueryBuilder('u');

        if ($criteria->getQuery()) {
            $qb->andWhere('u.fullName LIKE :query OR u.email LIKE :query')
                ->setParameter('query', '%' . $criteria->getQuery() . '%');
        }

        $qb->orderBy('u.' . $sort, $sortDirection);

        return new QueryAdapter($qb);
    }

    public function findStaff(): ?array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isStaff = :isStaff')
            ->setParameter('isStaff', true)
            ->getQuery()
            ->getResult();
    }

    public function getRandomUser(): ?User
    {
        // Execute a raw SQL query to fetch a single random user ID
        $conn = $this->getEntityManager()->getConnection();
        $sql = 'SELECT id FROM user WHERE is_verified=1 ORDER BY RAND() LIMIT 1';
        $userId = $conn->fetchOne($sql);

        // Fetch the user entity by its ID
        return $this->find($userId);
    }
}
