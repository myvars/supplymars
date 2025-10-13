<?php

namespace App\Tests\Audit\Application\Listener;

use App\Audit\Application\EventListener\SupplierStockChangeLogger;
use App\Audit\Domain\Model\StockChange\SupplierStockChangeLog;
use App\Audit\Domain\Repository\SupplierStockChangeLogRepository;
use App\Audit\Infrastructure\Logging\SupplierStockChangeLogWriter;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductStockWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Purchasing\UI\Http\ArgumentResolver\SupplierProductPublicIdResolver;
use App\Shared\Application\FlusherInterface;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use App\Tests\Shared\Factory\SupplierProductFactory;
use Doctrine\ORM\EntityManagerInterface;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;

final class SupplierStockChangeLoggerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testPersistsLogAndFlushesOnStockChangeEvent(): void
    {
        $sp = SupplierProductFactory::createOne();
        $publicId = $sp->getPublicId();
        $beforeStock = $sp->getStock();

        // Trigger a stock change domain event
        $sp->updateStock($beforeStock + 5);
        $events = $sp->releaseDomainEvents();

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::once())->method('flush');

        $writer = new SupplierStockChangeLogWriter(
            supplierStockChangeLogs: self::getContainer()->get(SupplierStockChangeLogRepository::class),
            validator: self::getContainer()->get(ValidatorInterface::class),
            flusher: $flusher
        );

        /** @var SupplierProductPublicIdResolver $resolver */
        $resolver = self::getContainer()->get(SupplierProductPublicIdResolver::class);

        /** @var MockObject&LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::never())->method('warning');

        $listener = new SupplierStockChangeLogger(
            changeLogWriter: $writer,
            publicIdResolver: $resolver,
            logger: $logger
        );

        foreach ($events as $event) {
            self::assertInstanceOf(SupplierProductStockWasChangedEvent::class, $event);
            $listener($event);
        }

        $this->em->flush();
        $this->em->clear();

        // Assert a log row exists for this legacy ID and type
        $repo = $this->em->getRepository(SupplierStockChangeLog::class);
        $logs = $repo->findBy(['supplierProductId' => $sp->getId()]);
        self::assertNotEmpty($logs);

        /** @var SupplierStockChangeLog $log */
        $log = $logs[2];
        self::assertSame($sp->getId(), $log->getSupplierProductId());
        self::assertSame($sp->getStock(), $log->getStock());
        self::assertSame((float) $sp->getCost(), (float) $log->getCost());
    }

    public function testDoesNotFlushAndWarnsWhenPublicIdCannotResolve(): void
    {
        $sp = SupplierProductFactory::createOne();

        // Make a synthetic event with a public ID that won't resolve
        $badEvent = new SupplierProductStockWasChangedEvent(
            id: SupplierProductPublicId::new(),
            stockChange: StockChange::from($sp->getStock() ?? 0, ($sp->getStock() ?? 0) + 1),
            costChange: CostChange::from($sp->getCost() ?? '0.00', $sp->getCost() ?? '0.00')
        );

        /** @var MockObject&FlusherInterface $flusher */
        $flusher = $this->createMock(FlusherInterface::class);
        $flusher->expects(self::never())->method('flush');

        $writer = new SupplierStockChangeLogWriter(
            supplierStockChangeLogs: self::getContainer()->get(SupplierStockChangeLogRepository::class),
            validator: self::getContainer()->get(ValidatorInterface::class),
            flusher: $flusher
        );

        /** @var SupplierProductPublicIdResolver $resolver */
        $resolver = self::getContainer()->get(SupplierProductPublicIdResolver::class);

        /** @var MockObject&LoggerInterface $logger */
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $listener = new SupplierStockChangeLogger(
            changeLogWriter: $writer,
            publicIdResolver: $resolver,
            logger: $logger
        );

        $listener($badEvent);

        $this->em->flush();
        $this->em->clear();

        $repo = $this->em->getRepository(SupplierStockChangeLog::class);
        $rows = $repo->findBy(['supplierProductId' => $sp->getId()]);
        self::assertCount(2, $rows);
    }
}
