<?php

declare(strict_types=1);

namespace App\Tests\Note\Application\Handler\Pool;

use App\Note\Application\Command\Pool\DeletePool;
use App\Note\Application\Handler\Pool\DeletePoolHandler;
use App\Note\Domain\Repository\PoolRepository;
use App\Tests\Shared\Factory\PoolFactory;
use App\Tests\Shared\Factory\TicketFactory;
use App\Tests\Shared\Story\StaffUserStory;
use App\Tests\Shared\Story\SuperAdminUserStory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Attribute\WithStory;
use Zenstruck\Foundry\Test\Factories;

final class DeletePoolHandlerTest extends KernelTestCase
{
    use Factories;

    private DeletePoolHandler $handler;

    private PoolRepository $pools;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeletePoolHandler::class);
        $this->pools = self::getContainer()->get(PoolRepository::class);
    }

    #[WithStory(StaffUserStory::class)]
    public function testRefusesStaffUser(): void
    {
        $pool = PoolFactory::createOne();

        $result = ($this->handler)(new DeletePool($pool->getPublicId()));

        self::assertFalse($result->ok);
        self::assertSame('Deleting is disabled for this user.', $result->message);
        self::assertNotNull($this->pools->getByPublicId($pool->getPublicId()));
    }

    #[WithStory(SuperAdminUserStory::class)]
    public function testFailsWhenPoolHasTickets(): void
    {
        $pool = PoolFactory::createOne();
        TicketFactory::createOne(['pool' => $pool]);

        $result = ($this->handler)(new DeletePool($pool->getPublicId()));

        self::assertFalse($result->ok);
        self::assertStringContainsString('Has tickets', $result->message);
        self::assertNotNull($this->pools->getByPublicId($pool->getPublicId()));
    }
}
