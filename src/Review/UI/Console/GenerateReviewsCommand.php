<?php

namespace App\Review\UI\Console;

use App\Review\Application\Service\ReviewGenerator;
use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Attribute\Option;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:generate-reviews',
    description: 'Generate product reviews for eligible delivered purchases',
)]
final readonly class GenerateReviewsCommand
{
    public function __construct(
        private ReviewGenerator $generator,
        private DefaultUserAuthenticator $authenticator,
        private FlusherInterface $flusher,
    ) {
    }

    public function __invoke(
        InputInterface $input,
        OutputInterface $output,
        #[Argument(description: 'Max reviews to generate')] int $count = 20,
        #[Argument(description: 'Optional product ID to target')] ?int $productId = null,
        #[Option(description: 'Run without persisting changes')]
        bool $dryRun = false,
    ): int {
        $io = new SymfonyStyle($input, $output);

        if ($count < 1) {
            $io->error('Count must be > 0');

            return Command::INVALID;
        }

        $this->authenticator->ensureAuthenticated();

        $io->section(sprintf(
            '%sGenerating up to %d reviews...',
            $dryRun ? '[DRY RUN] ' : '',
            $count
        ));

        $result = $this->generator->generate($count, $productId, $dryRun);

        if (!$dryRun) {
            $this->flusher->flush();
        }

        $io->success(sprintf(
            '%s%d reviews generated.',
            $dryRun ? '[DRY RUN] ' : '',
            $result['count']
        ));

        if ($output->isVerbose() && $result['count'] > 0) {
            $io->section('Created Review IDs');
            if ($dryRun) {
                $io->listing(array_map(fn (int $i): string => 'DRY-RUN-' . ($i + 1), range(0, $result['count'] - 1)));
            } else {
                $io->listing($result['ids']);
            }
        }

        return Command::SUCCESS;
    }
}
