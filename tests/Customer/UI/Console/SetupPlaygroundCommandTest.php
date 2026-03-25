<?php

namespace App\Tests\Customer\UI\Console;

use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Foundry\Test\Factories;

final class SetupPlaygroundCommandTest extends KernelTestCase
{
    use Factories;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $command = $application->find('app:setup-playground');
        $this->commandTester = new CommandTester($command);
    }

    public function testCommandFailsWhenNotInPlaygroundMode(): void
    {
        $this->commandTester->execute([]);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('playground mode', $this->commandTester->getDisplay());
    }
}
