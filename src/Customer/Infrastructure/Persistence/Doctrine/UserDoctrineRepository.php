<?php

namespace App\Customer\Infrastructure\Persistence\Doctrine;

use App\Customer\Application\Search\CustomerSearchCriteria;
use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Model\User\UserId;
use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\Domain\Repository\UserRepository;
use App\Order\Domain\Model\Order\OrderStatus;
use App\Review\Domain\Model\Review\ReviewStatus;
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
 * @method User|null find($id, $lockMode = null, $lockVersion = null)
 * @method User|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method User[]    findAll()
 * @method User[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
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

    /**
     * @return AdapterInterface<User>
     */
    public function findByCriteria(SearchCriteriaInterface $criteria): AdapterInterface
    {
        if (!$criteria instanceof CustomerSearchCriteria) {
            throw new \InvalidArgumentException('Expected CustomerSearchCriteria');
        }

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

    /**
     * @return array<int, User>|null
     */
    public function findStaff(): ?array
    {
        return $this->createQueryBuilder('u')
            ->where('u.isStaff = :isStaff')
            ->setParameter('isStaff', true)
            ->getQuery()
            ->getResult();
    }

    public function getStaffById(UserId $id): ?User
    {
        return $this->findOneBy(['id' => $id->value(), 'isStaff' => true]);
    }

    public function getByEmail(string $email): ?User
    {
        return $this->findOneBy(['email' => $email]);
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

    public function findByApiToken(string $token): ?User
    {
        return $this->findOneBy(['apiToken' => $token]);
    }

    public function countNonStaffCustomers(): int
    {
        return (int) $this->createQueryBuilder('u')
            ->select('COUNT(u.id)')
            ->where('u.isStaff = :isStaff')
            ->setParameter('isStaff', false)
            ->getQuery()
            ->getSingleScalarResult();
    }

    /**
     * @return array<string, mixed>
     */
    public function findCustomerInsights(User $user): array
    {
        $conn = $this->getEntityManager()->getConnection();

        $sql = '
            SELECT
                COALESCE(SUM(co.total_price_inc_vat), 0) AS totalRevenue,
                COUNT(co.id) AS orderCount,
                CASE WHEN COUNT(co.id) > 0 THEN SUM(co.total_price_inc_vat) / COUNT(co.id) ELSE 0 END AS averageOrderValue,
                MIN(co.created_at) AS firstOrderDate,
                MAX(co.created_at) AS lastOrderDate,
                DATEDIFF(NOW(), MAX(co.created_at)) AS daysSinceLastOrder,
                (SELECT COUNT(DISTINCT pr.id)
                 FROM product_review pr
                 WHERE pr.customer_id = :customerId
                 AND pr.status = :publishedStatus) AS reviewCount,
                (SELECT COUNT(*) + 1
                 FROM (
                     SELECT customer_id
                     FROM customer_order
                     WHERE status != :cancelledStatus
                     GROUP BY customer_id
                     HAVING SUM(total_price_inc_vat) > (
                         SELECT COALESCE(SUM(total_price_inc_vat), 0)
                         FROM customer_order
                         WHERE customer_id = :customerId AND status != :cancelledStatus
                     )
                 ) ranked) AS revenueRank,
                CASE
                    WHEN COUNT(co.id) = 0 THEN :segNew
                    WHEN COUNT(co.id) = 1 THEN :segNew
                    WHEN COUNT(co.id) BETWEEN 2 AND 3 THEN :segReturning
                    WHEN COUNT(co.id) >= 4 THEN :segLoyal
                    ELSE :segNew
                END AS segment
            FROM customer_order co
            WHERE co.customer_id = :customerId
            AND co.status != :cancelledStatus
        ';

        $result = $conn->fetchAssociative($sql, [
            'customerId' => $user->getId(),
            'cancelledStatus' => OrderStatus::CANCELLED->value,
            'publishedStatus' => ReviewStatus::PUBLISHED->value,
            'segNew' => 'new',
            'segReturning' => 'returning',
            'segLoyal' => 'loyal',
        ]);

        if (!$result) {
            return [
                'totalRevenue' => '0.00',
                'orderCount' => 0,
                'averageOrderValue' => '0.00',
                'firstOrderDate' => null,
                'lastOrderDate' => null,
                'daysSinceLastOrder' => null,
                'reviewCount' => 0,
                'revenueRank' => null,
                'segment' => 'new',
            ];
        }

        // Check for lapsed status
        if ((int) $result['daysSinceLastOrder'] > 60 && (int) $result['orderCount'] > 0) {
            $result['segment'] = 'lapsed';
        }

        return $result;
    }
}
