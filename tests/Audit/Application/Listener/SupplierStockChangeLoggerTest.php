<?php

declare(strict_types=1);

namespace App\Tests\Audit\Application\Listener;

use App\Audit\Application\Listener\SupplierStockChangeLogger;
use App\Audit\Infrastructure\Logging\SupplierStockChangeLogWriter;
use App\Purchasing\Domain\Model\SupplierProduct\Event\SupplierProductStockWasChangedEvent;
use App\Purchasing\Domain\Model\SupplierProduct\SupplierProductPublicId;
use App\Shared\Application\Identity\PublicIdResolverRegistry;
use App\Shared\Domain\ValueObject\CostChange;
use App\Shared\Domain\ValueObject\StockChange;
use App\Tests\Shared\Factory\SupplierProductFactory;
use App\Tests\Shared\Factory\SupplierStockChangeLogFactory;
use Doctrine\ORM\EntityManagerInterface;
use Psr\Log\LoggerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class SupplierStockChangeLoggerTest extends KernelTestCase
{
    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = self::getContainer()->get(EntityManagerInterface::class);
    }

    public function testLogsStockChangeOnFlush(): void
    {
        $sp = SupplierProductFactory::createOne(['stock' => 10, 'cost' => '4.00']);
        $logsBefore = SupplierStockChangeLogFactory::count(['supplierProductId' => $sp->getId()]);

        $sp->updateStock(15);
        $this->em->flush();
        $this->em->clear();

        $logs = SupplierStockChangeLogFactory::repository()->findBy(['supplierProductId' => $sp->getId()], ['id' => 'ASC']);
        self::assertCount($logsBefore + 1, $logs);
        $log = $logs[$logsBefore];
        self::assertSame(15, $log->getStock());
        self::assertSame('4.00', $log->getCost());
    }

    public function testWarnsAndWritesNothingWhenPublicIdCannotResolve(): void
    {
        $logger = $this->createMock(LoggerInterface::class);
        $logger->expects(self::once())->method('warning');

        $listener = new SupplierStockChangeLogger(
            changeLogWriter: self::getContainer()->get(SupplierStockChangeLogWriter::class),
            publicIdResolverRegistry: self::getContainer()->get(PublicIdResolverRegistry::class),
            logger: $logger,
        );

        $listener(new SupplierProductStockWasChangedEvent(
            id: SupplierProductPublicId::new(),
            stockChange: StockChange::from(10, 11),
            costChange: CostChange::from('4.00', '4.00'),
        ));

        SupplierStockChangeLogFactory::assert()->empty();
    }
}
