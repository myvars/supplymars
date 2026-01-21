<?php

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine;

use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Persistence\Doctrine\DoctrineFlusher;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\TestCase;

final class DoctrineFlusherTest extends TestCase
{
    public function testImplementsFlusherInterface(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $flusher = new DoctrineFlusher($em);

        self::assertInstanceOf(FlusherInterface::class, $flusher);
    }

    public function testFlushInvokesEntityManagerFlush(): void
    {
        $em = $this->createMock(EntityManagerInterface::class);
        $em->expects(self::once())
            ->method('flush');

        $flusher = new DoctrineFlusher($em);
        $flusher->flush();
    }

    public function testFlushBubblesUpExceptions(): void
    {
        $em = $this->createStub(EntityManagerInterface::class);
        $em
            ->method('flush')
            ->willThrowException(new \RuntimeException('db error'));

        $flusher = new DoctrineFlusher($em);

        self::expectException(\RuntimeException::class);
        self::expectExceptionMessage('db error');

        $flusher->flush();
    }
}
