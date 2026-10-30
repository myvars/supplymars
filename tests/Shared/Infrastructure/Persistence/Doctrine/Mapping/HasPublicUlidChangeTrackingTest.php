<?php

declare(strict_types=1);

namespace App\Tests\Shared\Infrastructure\Persistence\Doctrine\Mapping;

use App\Shared\Application\FlusherInterface;
use App\Tests\Shared\Factory\ProductFactory;
use App\Tests\Shared\Factory\PurchaseOrderFactory;
use App\Tests\Shared\Factory\UserFactory;
use Doctrine\ORM\EntityManagerInterface;
use Gedmo\Timestampable\Traits\TimestampableEntity;
use PHPUnit\Framework\Attributes\DataProvider;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

/**
 * Loading an entity with a public ULID and flushing without a change must not write the row.
 */
final class HasPublicUlidChangeTrackingTest extends KernelTestCase
{
    private const string OLD_UPDATED_AT = '2020-01-01 00:00:00';

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();

        $em = self::getContainer()->get(EntityManagerInterface::class);
        \assert($em instanceof EntityManagerInterface);
        $this->em = $em;
    }

    /**
     * @return iterable<string, array{\Closure(): object}>
     */
    public static function entities(): iterable
    {
        yield 'user' => [static fn (): object => UserFactory::createOne()];
        yield 'product' => [static fn (): object => ProductFactory::createOne()];
        yield 'purchase order' => [static fn (): object => PurchaseOrderFactory::createOne()];
    }

    /**
     * @param \Closure(): object $create
     */
    #[DataProvider('entities')]
    public function testFlushingAnUnchangedEntityWritesNothing(\Closure $create): void
    {
        [$class, $id] = $this->backdate($create());

        $entity = $this->em->find($class, $id);
        self::assertNotNull($entity);

        $uow = $this->em->getUnitOfWork();
        $uow->computeChangeSets();
        self::assertSame([], $uow->getEntityChangeSet($entity));

        $flusher = self::getContainer()->get(FlusherInterface::class);
        \assert($flusher instanceof FlusherInterface);
        self::assertFalse($flusher->flush());

        $this->em->clear();
        $reloaded = $this->em->find($class, $id);
        self::assertNotNull($reloaded);
        \assert(method_exists($reloaded, 'getUpdatedAt'));
        self::assertSame(self::OLD_UPDATED_AT, $reloaded->getUpdatedAt()->format('Y-m-d H:i:s'));
    }

    public function testFlushingAChangedEntityStillWritesIt(): void
    {
        [$class, $id] = $this->backdate(UserFactory::createOne());

        $user = $this->em->find($class, $id);
        self::assertNotNull($user);
        $user->setFullName('Changed Name');

        $flusher = self::getContainer()->get(FlusherInterface::class);
        \assert($flusher instanceof FlusherInterface);
        self::assertTrue($flusher->flush());

        $this->em->clear();
        $reloaded = $this->em->find($class, $id);
        self::assertNotNull($reloaded);
        self::assertSame('Changed Name', $reloaded->getFullName());
        self::assertNotSame(self::OLD_UPDATED_AT, $reloaded->getUpdatedAt()?->format('Y-m-d H:i:s'));
    }

    /**
     * Backdates the entity's updatedAt, so a rewrite would show, and clears the entity manager.
     *
     * @template T of object
     *
     * @param T $entity
     *
     * @return array{class-string<T>, int}
     */
    private function backdate(object $entity): array
    {
        $class = $entity::class;
        \assert(in_array(TimestampableEntity::class, class_uses($class), true));
        \assert(method_exists($entity, 'getId'));
        $id = $entity->getId();
        \assert(is_int($id));

        $this->em->createQuery(sprintf('UPDATE %s e SET e.updatedAt = :old WHERE e.id = :id', $class))
            ->setParameter('old', new \DateTime(self::OLD_UPDATED_AT))
            ->setParameter('id', $id)
            ->execute();
        $this->em->clear();

        return [$class, $id];
    }
}
