<?php

namespace App\Note\Infrastructure\Persistence\Doctrine;

use App\Note\Domain\Model\Message\Message;
use App\Note\Domain\Repository\MessageRepository;
use Doctrine\Bundle\DoctrineBundle\Repository\ServiceEntityRepository;
use Doctrine\Persistence\ManagerRegistry;

/**
 * @extends ServiceEntityRepository<Message>
 *
 * @method Message|null find($id, $lockMode = null, $lockVersion = null)
 * @method Message|null findOneBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null)
 * @method Message[]    findAll()
 * @method Message[]    findBy(array<string, mixed> $criteria, ?array<string, string> $orderBy = null, $limit = null, $offset = null)
 */
class MessageDoctrineRepository extends ServiceEntityRepository implements MessageRepository
{
    public function __construct(ManagerRegistry $registry)
    {
        parent::__construct($registry, Message::class);
    }

    public function add(Message $message): void
    {
        $this->getEntityManager()->persist($message);
    }

    public function remove(Message $message): void
    {
        $this->getEntityManager()->remove($message);
    }
}
