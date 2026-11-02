<?php

declare(strict_types=1);

namespace App\Tests\Customer\UI\Console;

use App\Tests\Shared\Factory\UserFactory;
use Doctrine\DBAL\Connection;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Tester\ExecutionResult;
use Zenstruck\Browser\KernelBrowser;
use Zenstruck\Browser\Test\HasBrowser;

final class IssueApiTokenCommandTest extends KernelTestCase
{
    use HasBrowser;

    public function testIssuesATokenThatAuthenticatesTheUser(): void
    {
        UserFactory::new(['email' => 'api-user@example.com'])->asStaff()->create();

        $result = self::runCommand('app:user:issue-token', ['email' => 'api-user@example.com']);

        $this->assertCommandIsSuccessful($result);
        $this->getOrdersWith($this->printedToken($result))->assertSuccessful();
    }

    public function testStoresOnlyTheHashOfTheToken(): void
    {
        UserFactory::createOne(['email' => 'api-user@example.com']);

        $result = self::runCommand('app:user:issue-token', ['email' => 'api-user@example.com']);
        $token = $this->printedToken($result);

        /** @var Connection $connection */
        $connection = self::getContainer()->get(Connection::class);
        $stored = $connection->fetchOne('SELECT api_token_hash FROM user WHERE email = ?', ['api-user@example.com']);

        self::assertSame(hash('sha256', $token), $stored);
    }

    public function testReplacesTheExistingToken(): void
    {
        UserFactory::new(['email' => 'api-user@example.com'])->asStaff()->withApiToken('old-token')->create();

        $result = self::runCommand('app:user:issue-token', ['email' => 'api-user@example.com']);

        $this->assertCommandIsSuccessful($result);
        self::assertStringContainsString('previous token no longer works', $result->getDisplay());
        $this->getOrdersWith('old-token')->assertStatus(401);
    }

    public function testPrintsOnlyTheTokenOnStdoutEvenWhenQuiet(): void
    {
        UserFactory::createOne(['email' => 'api-user@example.com']);

        $result = self::runCommand(
            'app:user:issue-token',
            ['email' => 'api-user@example.com'],
            verbosity: OutputInterface::VERBOSITY_QUIET,
        );

        self::assertMatchesRegularExpression('/^[0-9a-f]{64}\n$/', $result->getOutput());
    }

    public function testFailsForAnUnknownEmail(): void
    {
        $result = self::runCommand('app:user:issue-token', ['email' => 'nobody@example.com']);

        $this->assertCommandFailed($result);
        self::assertStringContainsString('No user with email "nobody@example.com"', $result->getDisplay());
    }

    private function printedToken(ExecutionResult $result): string
    {
        $matched = preg_match('/^([0-9a-f]{64})$/m', $result->getDisplay(), $matches);
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
