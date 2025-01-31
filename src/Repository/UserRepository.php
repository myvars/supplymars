<?php

namespace App\Repository;

use App\DTO\SearchDto\SearchInterface;
use App\Entity\User;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\ORM\QueryBuilder;
use Doctrine\Persistence\ManagerRegistry;
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
class UserRepository extends ServiceEntityRepository implements PasswordUpgraderInterface, SearchQueryInterface
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, User::class);
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

    public function findBySearchDto(SearchInterface $searchDto): QueryBuilder
    {
        $sort = $searchDto->getSort() ?: $searchDto::SORT_DEFAULT;
        $sortDirection = $searchDto->getSortDirection() ?: $searchDto::SORT_DIRECTION_DEFAULT;

        $qb = $this->createQueryBuilder('u');

        if ($searchDto->getQuery()) {
            $qb->andWhere('u.fullName LIKE :query OR u.email LIKE :query')
                ->setParameter('query', '%'.$searchDto->getQuery().'%');
        }

        $qb->orderBy('u.'.$sort, $sortDirection);

        return $qb;
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
        $sql = 'SELECT id FROM user ORDER BY RAND() LIMIT 1';
        $userId = $conn->fetchOne($sql);

        // Fetch the user entity by its ID
        return $this->find($userId);
    }
}
