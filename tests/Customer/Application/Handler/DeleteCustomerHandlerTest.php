<?php

namespace App\Tests\Customer\Application\Handler;

use App\Customer\Application\Command\DeleteCustomer;
use App\Customer\Application\Handler\DeleteCustomerHandler;
use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\Domain\Repository\UserRepository;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class DeleteCustomerHandlerTest extends KernelTestCase
{
    use Factories;

    private DeleteCustomerHandler $handler;

    private UserRepository $customers;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(DeleteCustomerHandler::class);
        $this->customers = self::getContainer()->get(UserRepository::class);
    }

    public function testDeletesExistingCustomer(): void
    {
        $customer = UserFactory::createOne();
        $publicId = $customer->getPublicId();

        $command = new DeleteCustomer($publicId);
        $result = ($this->handler)($command);

        self::assertTrue($result->ok);
        self::assertNull($this->customers->getByPublicId($publicId));
    }

    public function testFailsWhenCustomerNotFound(): void
    {
        $missingId = UserPublicId::new();

        $command = new DeleteCustomer($missingId);
        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Customer not found', $result->message);
    }

    public function testFailsWhenCustomerNotDeletable(): void
    {
        $admin = UserFactory::new()->asStaff()->create();
        $publicId = $admin->getPublicId();

        $command = new DeleteCustomer($publicId);
        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Customer cannot be deleted', $result->message);
        self::assertNotNull($this->customers->getByPublicId($publicId));
    }
}
