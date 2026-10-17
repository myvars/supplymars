<?php

declare(strict_types=1);

namespace App\Tests\Note\Application\Handler\Pool;

use App\Note\Application\Command\Pool\CreatePool;
use App\Note\Application\Handler\Pool\CreatePoolHandler;
use App\Tests\Shared\Factory\PoolFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class CreatePoolHandlerTest extends KernelTestCase
{
    use Factories;

    private CreatePoolHandler $handler;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(CreatePoolHandler::class);
    }

    public function testHandleRefusesAnOverLengthDescriptionFromTheEntityRules(): void
    {
        // No form in front of the handler: the pool's own Length rule must stop the save.
        $result = ($this->handler)(new CreatePool(
            name: 'Product questions',
            description: str_repeat('a', 10001),
            isActive: true,
            isCustomerVisible: true,
        ));

        self::assertFalse($result->ok);
        self::assertStringContainsString('Max 10000 characters', (string) $result->message);
        PoolFactory::assert()->empty();
    }
}
