<?php

namespace App\Tests\Integration\Service\Search;

use App\Customer\Domain\Model\User\User;
use App\Service\Search\Paginator;
use Doctrine\ORM\EntityManagerInterface;
use tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

class PaginatorIntegrationTest extends KernelTestCase
{
    use Factories;

    private Paginator $paginator;

    private EntityManagerInterface $em;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->em = static::getContainer()->get(EntityManagerInterface::class);
        $this->paginator = new Paginator();
    }

    public function testCreatePagination(): void
    {
        UserFactory::createMany(20);

        $queryBuilder = $this->em->createQueryBuilder()
            ->select('u')
            ->from(User::class, 'u');

        $page = 1;
        $limit = 10;

        $pagination = $this->paginator->createPagination($queryBuilder, $page, $limit);

        $this->assertSame($page, $pagination->getCurrentPage());
        $this->assertSame($limit, $pagination->getMaxPerPage());
        $this->assertCount($limit, $pagination->getCurrentPageResults());
    }
}
