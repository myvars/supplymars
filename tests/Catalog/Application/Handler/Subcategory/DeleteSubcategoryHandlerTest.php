<?php

namespace App\Tests\Catalog\Application\Handler\Subcategory;

use App\Catalog\Application\Command\Subcategory\DeleteSubcategory;
use App\Catalog\Application\Handler\Subcategory\DeleteSubcategoryHandler;
use App\Catalog\Domain\Model\Subcategory\SubcategoryPublicId;
use App\Catalog\Domain\Repository\SubcategoryRepository;
use App\Tests\Shared\Factory\SubcategoryFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class DeleteSubcategoryHandlerTest extends KernelTestCase
{
    use Factories;

    private DeleteSubcategoryHandler $handler;
    private SubcategoryRepository $categories;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteSubcategoryHandler::class);
        $this->subcategories = self::getContainer()->get(SubcategoryRepository::class);
    }

    public function testDeletesExistingSubcategory(): void
    {
        $subcategory = SubcategoryFactory::createOne();
        $publicId = $subcategory->getPublicId();

        $command = new DeleteSubcategory($publicId);

        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertSame('Subcategory deleted', $result->message);
        self::assertNull($this->subcategories->getByPublicId($publicId));
    }

    public function testFailsWhenSubcategoryNotFound(): void
    {
        $missingId = SubcategoryPublicId::new();

        $command = new DeleteSubcategory($missingId);

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Subcategory not found', $result->message);
    }
}
