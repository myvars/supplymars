<?php

declare(strict_types=1);

namespace App\Customer\UI\Console;

use App\Customer\Domain\Model\User\User;
use App\Customer\Domain\Repository\UserRepository;
use App\Shared\Application\FlusherInterface;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:user:issue-token',
    description: 'Issue a new API token for a user, replacing any existing one. The token is shown once.',
)]
final readonly class IssueApiTokenCommand
{
    public function __construct(
        private UserRepository $users,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        SymfonyStyle $io,
        #[Argument(description: 'Email of the user')]
        string $email,
    ): int {
        $user = $this->users->getByEmail($email);
        if (!$user instanceof User) {
            $io->error(sprintf('No user with email "%s".', $email));

            return Command::FAILURE;
        }

        $replaced = $user->hasApiToken();
        $apiToken = $user->regenerateApiToken();
        $this->flusher->flush();

        // Messages go to stderr so the token is the only line on stdout, and it is printed even with --quiet.
        $errorIo = $io->getErrorStyle();
        $errorIo->success(sprintf('API token issued for %s. Copy it now: it is stored hashed and cannot be shown again.', $email));
        if ($replaced) {
            $errorIo->note('The previous token no longer works.');
        }

        $io->writeln($apiToken, OutputInterface::VERBOSITY_QUIET);

        return Command::SUCCESS;
    }
}
