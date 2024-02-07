<?php

namespace App\Test\Controller;

use App\Entity\Product;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\EntityRepository;
use Symfony\Bundle\FrameworkBundle\KernelBrowser;
use Symfony\Bundle\FrameworkBundle\Test\WebTestCase;

class ProductControllerTest extends WebTestCase
{
    private KernelBrowser $client;
    private EntityManagerInterface $manager;
    private EntityRepository $repository;
    private string $path = '/product/';

    protected function setUp(): void
    {
        $this->client = static::createClient();
        $this->manager = static::getContainer()->get('doctrine')->getManager();
        $this->repository = $this->manager->getRepository(Product::class);

        foreach ($this->repository->findAll() as $object) {
            $this->manager->remove($object);
        }

        $this->manager->flush();
    }

    public function testIndex(): void
    {
        $crawler = $this->client->request('GET', $this->path);

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Product index');

        // Use the $crawler to perform additional assertions e.g.
        // self::assertSame('Some text on the page', $crawler->filter('.p')->first());
    }

    public function testNew(): void
    {
        $this->markTestIncomplete();
        $this->client->request('GET', sprintf('%snew', $this->path));

        self::assertResponseStatusCodeSame(200);

        $this->client->submitForm('Save', [
            'product[name]' => 'Testing',
            'product[MfrPartNumber]' => 'Testing',
            'product[stock]' => 'Testing',
            'product[leadTimeDays]' => 'Testing',
            'product[weight]' => 'Testing',
            'product[markup]' => 'Testing',
            'product[cost]' => 'Testing',
            'product[sellPrice]' => 'Testing',
            'product[isActive]' => 'Testing',
            'product[vatRate]' => 'Testing',
            'product[category]' => 'Testing',
            'product[subcategory]' => 'Testing',
            'product[manufacturer]' => 'Testing',
            'product[owner]' => 'Testing',
        ]);

        self::assertResponseRedirects('/sweet/food/');

        self::assertSame(1, $this->getRepository()->count([]));
    }

    public function testShow(): void
    {
        $this->markTestIncomplete();
        $fixture = new Product();
        $fixture->setName('My Title');
        $fixture->setMfrPartNumber('My Title');
        $fixture->setStock('My Title');
        $fixture->setLeadTimeDays('My Title');
        $fixture->setWeight('My Title');
        $fixture->setMarkup('My Title');
        $fixture->setCost('My Title');
        $fixture->setSellPrice('My Title');
        $fixture->setIsActive('My Title');
        $fixture->setVatRate('My Title');
        $fixture->setCategory('My Title');
        $fixture->setSubcategory('My Title');
        $fixture->setManufacturer('My Title');
        $fixture->setOwner('My Title');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));

        self::assertResponseStatusCodeSame(200);
        self::assertPageTitleContains('Product');

        // Use assertions to check that the properties are properly displayed.
    }

    public function testEdit(): void
    {
        $this->markTestIncomplete();
        $fixture = new Product();
        $fixture->setName('Value');
        $fixture->setMfrPartNumber('Value');
        $fixture->setStock('Value');
        $fixture->setLeadTimeDays('Value');
        $fixture->setWeight('Value');
        $fixture->setMarkup('Value');
        $fixture->setCost('Value');
        $fixture->setSellPrice('Value');
        $fixture->setIsActive('Value');
        $fixture->setVatRate('Value');
        $fixture->setCategory('Value');
        $fixture->setSubcategory('Value');
        $fixture->setManufacturer('Value');
        $fixture->setOwner('Value');

        $this->manager->persist($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s/edit', $this->path, $fixture->getId()));

        $this->client->submitForm('Update', [
            'product[name]' => 'Something New',
            'product[MfrPartNumber]' => 'Something New',
            'product[stock]' => 'Something New',
            'product[leadTimeDays]' => 'Something New',
            'product[weight]' => 'Something New',
            'product[markup]' => 'Something New',
            'product[cost]' => 'Something New',
            'product[sellPrice]' => 'Something New',
            'product[isActive]' => 'Something New',
            'product[vatRate]' => 'Something New',
            'product[category]' => 'Something New',
            'product[subcategory]' => 'Something New',
            'product[manufacturer]' => 'Something New',
            'product[owner]' => 'Something New',
        ]);

        self::assertResponseRedirects('/product/');

        $fixture = $this->repository->findAll();

        self::assertSame('Something New', $fixture[0]->getName());
        self::assertSame('Something New', $fixture[0]->getMfrPartNumber());
        self::assertSame('Something New', $fixture[0]->getStock());
        self::assertSame('Something New', $fixture[0]->getLeadTimeDays());
        self::assertSame('Something New', $fixture[0]->getWeight());
        self::assertSame('Something New', $fixture[0]->getMarkup());
        self::assertSame('Something New', $fixture[0]->getCost());
        self::assertSame('Something New', $fixture[0]->getSellPrice());
        self::assertSame('Something New', $fixture[0]->getIsActive());
        self::assertSame('Something New', $fixture[0]->getVatRate());
        self::assertSame('Something New', $fixture[0]->getCategory());
        self::assertSame('Something New', $fixture[0]->getSubcategory());
        self::assertSame('Something New', $fixture[0]->getManufacturer());
        self::assertSame('Something New', $fixture[0]->getOwner());
    }

    public function testRemove(): void
    {
        $this->markTestIncomplete();
        $fixture = new Product();
        $fixture->setName('Value');
        $fixture->setMfrPartNumber('Value');
        $fixture->setStock('Value');
        $fixture->setLeadTimeDays('Value');
        $fixture->setWeight('Value');
        $fixture->setMarkup('Value');
        $fixture->setCost('Value');
        $fixture->setSellPrice('Value');
        $fixture->setIsActive('Value');
        $fixture->setVatRate('Value');
        $fixture->setCategory('Value');
        $fixture->setSubcategory('Value');
        $fixture->setManufacturer('Value');
        $fixture->setOwner('Value');

        $this->manager->remove($fixture);
        $this->manager->flush();

        $this->client->request('GET', sprintf('%s%s', $this->path, $fixture->getId()));
        $this->client->submitForm('Delete');

        self::assertResponseRedirects('/product/');
        self::assertSame(0, $this->repository->count([]));
    }
}
