<?php

namespace App\Customer\UI\Console;

use App\Customer\Domain\Model\User\User;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;

#[AsCommand(
    name: 'app:setup-playground',
    description: 'Prepare playground environment: reset staff passwords and create demo user.',
)]
final readonly class SetupPlaygroundCommand
{
    private const string DEMO_EMAIL = 'demo@supplymars.com';

    private const string DEMO_NAME = 'Demo User';

    private const string DEMO_PASSWORD = 'demo';

    public function __construct(
        private EntityManagerInterface $entityManager,
        private UserPasswordHasherInterface $passwordHasher,
        #[Autowire('%env(bool:PLAYGROUND_MODE)%')]
        private bool $playgroundMode = false,
    ) {
    }

    public function __invoke(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);

        if (!$this->playgroundMode) {
            $io->error('This command can only run in playground mode. Set PLAYGROUND_MODE=1.');

            return Command::FAILURE;
        }

        $this->resetStaffPasswords($io);
        $this->createOrResetDemoUser($io);

        $this->entityManager->flush();

        $io->success('Playground setup complete.');

        return Command::SUCCESS;
    }

    private function resetStaffPasswords(SymfonyStyle $io): void
    {
        $staffUsers = $this->entityManager->getRepository(User::class)->findBy(['isStaff' => true]);
        $count = 0;

        foreach ($staffUsers as $user) {
            if ($user->getEmail() === self::DEMO_EMAIL) {
                continue;
            }

            $randomPassword = bin2hex(random_bytes(32));
            $user->setPassword($this->passwordHasher->hashPassword($user, $randomPassword));
            $user->setEmail(bin2hex(random_bytes(8)) . '@redacted.local');
            ++$count;
        }

        $io->info(sprintf('Reset %d staff password(s).', $count));
    }

    private function createOrResetDemoUser(SymfonyStyle $io): void
    {
        $user = $this->entityManager->getRepository(User::class)->findOneBy(['email' => self::DEMO_EMAIL]);

        if ($user === null) {
            $user = User::create(
                fullName: self::DEMO_NAME,
                email: self::DEMO_EMAIL,
                isStaff: true,
                isVerified: true,
            );
            $this->entityManager->persist($user);
            $io->info('Created demo user.');
        } else {
            $user->update(
                fullName: self::DEMO_NAME,
                email: self::DEMO_EMAIL,
                isStaff: true,
                isVerified: true,
            );
            $io->info('Reset existing demo user.');
        }

        $user->setPassword($this->passwordHasher->hashPassword($user, self::DEMO_PASSWORD));
    }
}
