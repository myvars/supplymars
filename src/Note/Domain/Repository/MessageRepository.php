<?php

namespace App\Note\Domain\Repository;

use App\Note\Domain\Model\Message\Message;
use App\Note\Infrastructure\Persistence\Doctrine\MessageDoctrineRepository;
use Symfony\Component\DependencyInjection\Attribute\AsAlias;

#[AsAlias(MessageDoctrineRepository::class)]
interface MessageRepository
{
    public function add(Message $message): void;

    public function remove(Message $message): void;
}
