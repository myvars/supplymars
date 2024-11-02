<?php

namespace App\Command;

use App\Entity\Address;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputArgument;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;
use Symfony\Component\Console\Style\SymfonyStyle;

#[AsCommand(
    name: 'app:fix-address',
    description: 'Fix address data',
)]
class fixAddressCommand extends Command
{
    public function __construct(
        private readonly EntityManagerInterface $entityManager
    ) {
        parent::__construct();
    }

    protected function configure(): void
    {
        $this->addArgument('addressCount', InputArgument::REQUIRED, 'Address count to process');
        $this->addArgument('offset', InputArgument::REQUIRED, 'Offset');
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $io = new SymfonyStyle($input, $output);
        $addressCount = $input->getArgument('addressCount');
        $offset = $input->getArgument('offset');

        $addresses = $this->getAddresses($addressCount, $offset);

        $addressCount = 0;
        foreach ($addresses as $address) {

            $cityData = $this->marsCity();

            $address->setCity($cityData['name']);
            $address->setCountry('Mars Colony');
            $address->setCounty('Red Zone');
            $address->setPostCode($cityData['sectorCode'] . '-' . random_int(10, 50));

            $this->entityManager->flush();

            $addressCount++;
            $io->note(sprintf('Customer %05d address fixed', $address->getCustomer()->getId()));
        }

        $io->success(sprintf('Processed %d Addresses', $addressCount));

        return Command::SUCCESS;
    }

    public function getAddresses(int $count, int $offset): array {
        return $this->entityManager->getRepository(Address::class)->findBy([], null, $count, $offset);
    }

    protected function marsCity(): array
    {
        $marsCities = [
            ['name' => 'Olympia', 'sectorCode' => 'OM'],
            ['name' => 'Vallis', 'sectorCode' => 'VM'],
            ['name' => 'Gale', 'sectorCode' => 'GC'],
            ['name' => 'Elysium', 'sectorCode' => 'EP'],
            ['name' => 'Red Dune', 'sectorCode' => 'RD'],
            ['name' => 'Crimson', 'sectorCode' => 'CP'],
            ['name' => 'Ironhold', 'sectorCode' => 'ID'],
            ['name' => 'Arcadia', 'sectorCode' => 'AP'],
            ['name' => 'Amazonis', 'sectorCode' => 'AS'],
            ['name' => 'Hellas', 'sectorCode' => 'HB'],
            ['name' => 'Isidis', 'sectorCode' => 'IP'],
            ['name' => 'Noctis', 'sectorCode' => 'NL'],
            ['name' => 'Cydonia', 'sectorCode' => 'CY'],
            ['name' => 'Tharsis', 'sectorCode' => 'TH'],
            ['name' => 'Utopia', 'sectorCode' => 'UP'],
        ];

        return $marsCities[array_rand($marsCities)];
    }
}