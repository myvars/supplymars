<?php

namespace App\Review\UI\Console;

use App\Review\Application\Service\ReviewGenerator;
use App\Shared\Application\FlusherInterface;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use Symfony\Component\Console\Attribute\Argument;
use Symfony\Component\Console\Attribute\AsCommand;
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
    ): int {
        $io = new SymfonyStyle($input, $output);

        $this->authenticator->ensureAuthenticated();

        $io->info(sprintf('Generating up to %d reviews...', $count));

        $created = $this->generator->generate($count, $productId);
        $this->flusher->flush();

        $io->success(sprintf('%d reviews generated.', $created));

        return Command::SUCCESS;
    }
}
