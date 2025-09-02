<?php

namespace App\Command\Utilities;

use App\Entity\HasPublicUlid;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\Mapping\ClassMetadata;
use Doctrine\ORM\Query;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:backfill-ulids',
    description: 'Backfill missing ULIDs for all entities using the HasPublicUlid trait.'
)]
final readonly class backfillUlidsCommand
{
    public function __construct(private EntityManagerInterface $em) {}

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(
            description: 'Batch size (defaults to 200).'
        )] int $batchSize = 200,
        #[Option(description: 'Maximum number of rows to process per entity')] ?int $limit = null
    ): int {
        $entities = $this->entitiesUsingHasPublicUlid();

        if ($entities === []) {
            $io->success('No entities found using HasPublicUlid.');
            return 0;
        }

        foreach ($entities as $meta) {
            $this->backfillForEntity($io, $meta, $batchSize, $limit);
        }

        $io->success('Backfill complete for all entities with HasPublicUlid.');
        return 0;
    }

    /**
     * @return list<ClassMetadata>
     */
    private function entitiesUsingHasPublicUlid(): array
    {
        /** @var list<ClassMetadata> $all */
        $all = $this->em->getMetadataFactory()->getAllMetadata();

        return array_values(array_filter(
            $all,
            static fn (ClassMetadata $meta) =>
            in_array(HasPublicUlid::class, class_uses($meta->getName()), true)
        ));
    }

    private function backfillForEntity(SymfonyStyle $io, ClassMetadata $meta, int $batchSize, ?int $limit): void
    {
        $entityClass = $meta->getName();
        $repo = $this->em->getRepository($entityClass);

        // Count rows missing a publicId
        $total = (int) $repo->createQueryBuilder('e')
            ->select('COUNT(e.id)')
            ->where('e.publicId IS NULL')
            ->getQuery()
            ->getSingleScalarResult();

        if ($total === 0) {
            $io->section("{$entityClass}: nothing to backfill.");
            return;
        }

        $target = $limit !== null ? min($total, $limit) : $total;

        $io->section("Backfilling {$entityClass}");
        $io->writeln("  Missing: <comment>{$total}</comment>  |  Processing: <comment>{$target}</comment>  |  Batch: <comment>{$batchSize}</comment>");

        // Streaming query for rows to process
        $qb = $repo->createQueryBuilder('e')
            ->where('e.publicId IS NULL')
            ->orderBy('e.id', 'ASC');

        if ($limit !== null) {
            $qb->setMaxResults($limit);
        }

        $query = $qb->getQuery()->setHydrationMode(Query::HYDRATE_OBJECT);
        $iterable = $query->toIterable();

        // Progress bar: iterate() auto-advances
        $progress = $io->createProgressBar($target);
        $progress->setRedrawFrequency(max(1, (int) floor($target / 100)));
        $progress->start();

        $processed = 0;

        foreach ($progress->iterate($iterable) as $entity) {
            // Provided by HasPublicUlid trait
            $entity->initializePublicId();
            ++$processed;

            if ($processed % $batchSize === 0) {
                $this->em->flush();
                $this->em->clear();
            }
        }

        // Flush remainder
        $this->em->flush();
        $this->em->clear();

        $progress->finish();
        $io->newLine(2);
        $io->writeln("  Done. Backfilled <info>{$processed}</info> rows.");
    }
}
