<?php

namespace App\Tests\Integration\Service\Search;

use App\Factory\UserFactory;
use App\Service\Search\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class PaginatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private Paginator $paginator;
    private EntityManagerInterface $entityManager;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->entityManager = static::getContainer()->get(EntityManagerInterface::class);
        $this->paginator = new Paginator();
    }

    public function testCreatePagination(): void
    {
        UserFactory::createMany(20);

        $queryBuilder = $this->entityManager->createQueryBuilder()
            ->select('u')
            ->from('App\Entity\User', 'u');

        $page = 1;
        $limit = 10;

        $pagination = $this->paginator->createPagination($queryBuilder, $page, $limit);

        $this->assertSame($page, $pagination->getCurrentPage());
        $this->assertSame($limit, $pagination->getMaxPerPage());
        $this->assertCount($limit, $pagination->getCurrentPageResults());
    }
}