<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI\Console;

use App\Tests\Shared\Factory\UserFactory;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Console\Application;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\CommandTester;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

final class IssueApiTokenCommandTest extends KernelTestCase
{
    use HasBrowser;

    private CommandTester $commandTester;

    protected function setUp(): void
    {
        self::bootKernel();
        $application = new Application(self::$kernel);
        $this->commandTester = new CommandTester($application->find('app:user:issue-token'));
    }

    public function testIssuesATokenThatAuthenticatesTheUser(): void
    {
        UserFactory::new(['email' => 'api-user@example.com'])->asStaff()->create();

        $this->commandTester->execute(['email' => 'api-user@example.com']);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        $this->getOrdersWith($this->printedToken())->assertSuccessful();
    }

    public function testStoresOnlyTheHashOfTheToken(): void
    {
        UserFactory::createOne(['email' => 'api-user@example.com']);

        $this->commandTester->execute(['email' => 'api-user@example.com']);
        $token = $this->printedToken();

        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);
        $stored = $connection->fetchOne('SELECT api_token_hash FROM user WHERE email = ?', ['api-user@example.com']);

        self::assertSame(hash('sha256', $token), $stored);
    }

    public function testReplacesTheExistingToken(): void
    {
        UserFactory::new(['email' => 'api-user@example.com'])->asStaff()->withApiToken('old-token')->create();

        $this->commandTester->execute(['email' => 'api-user@example.com']);

        self::assertSame(Command::SUCCESS, $this->commandTester->getStatusCode());
        self::assertStringContainsString('previous token no longer works', $this->commandTester->getDisplay());
        $this->getOrdersWith('old-token')->assertStatus(401);
    }

    public function testPrintsOnlyTheTokenOnStdoutEvenWhenQuiet(): void
    {
        UserFactory::createOne(['email' => 'api-user@example.com']);

        $this->commandTester->execute(
            ['email' => 'api-user@example.com'],
            ['verbosity' => OutputInterface::VERBOSITY_QUIET, 'capture_stderr_separately' => true],
        );

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}\n$/', $this->commandTester->getDisplay());
    }

    public function testFailsForAnUnknownEmail(): void
    {
        $this->commandTester->execute(['email' => 'nobody@example.com']);

        self::assertSame(Command::FAILURE, $this->commandTester->getStatusCode());
        self::assertStringContainsString('No user with email "nobody@example.com"', $this->commandTester->getDisplay());
    }

    private function printedToken(): string
    {
        $matched = preg_match('/^([0-9a-f]{64})$/m', $this->commandTester->getDisplay(), $matches);
        self::assertSame(1, $matched, 'The command did not print a token.');

        return $matches[1] ?? '';
    }

    private function getOrdersWith(string $token): KernelBrowser
    {
        return $this->browser()->get('/api/v1/orders', [
            'headers' => ['Authorization' => 'Bearer ' . $token],
        ]);
    }
}
