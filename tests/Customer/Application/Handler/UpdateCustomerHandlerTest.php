<?php

namespace App\Tests\Customer\Application\Handler;

use App\Customer\Application\Command\UpdateCustomer;
use App\Customer\Application\Handler\UpdateCustomerHandler;
use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\Domain\Repository\UserRepository;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Zenstruck\Foundry\Test\Factories;

final class UpdateCustomerHandlerTest extends KernelTestCase
{
    use Factories;

    private UpdateCustomerHandler $handler;
    private UserRepository $users;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->handler = self::getContainer()->get(UpdateCustomerHandler::class);
        $this->users = self::getContainer()->get(UserRepository::class);
    }

    public function testHandleUpdatesCustomer(): void
    {
        $user = UserFactory::new()->asStaff()->create();

        $command = new UpdateCustomer(
            id: $user->getPublicId(),
            fullName: 'Updated User',
            email: 'updated@example.com',
            isVerified: false,
            isStaff: false,
        );

        $result = ($this->handler)($command);
        self::assertTrue($result->ok);

        $persisted = $this->users->getByPublicId($user->getPublicId());
        self::assertSame('Updated User', $persisted->getFullName());
        self::assertSame('updated@example.com', $persisted->getEmail());
        self::assertFalse($persisted->isVerified());
        self::assertFalse($persisted->isStaff());
        self::assertFalse($persisted->isAdmin());
    }

    public function testFailsWhenCustomerNotFound(): void
    {
        $missingId = UserPublicId::new();

        $command = new UpdateCustomer(
            id: $missingId,
            fullName: 'X',
            email: 'x@example.com',
            isVerified: true,
            isStaff: true,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Customer not found', $result->message);
    }

    public function testFailsOnValidationErrors(): void
    {
        $user = UserFactory::new()->create();

        $command = new UpdateCustomer(
            id: $user->getPublicId(),
            fullName: '', // NotBlank violation
            email: 'not-an-email', // Email violation
            isVerified: true,
            isStaff: false,
        );

        $result = ($this->handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Please enter a full name', $result->message);
        self::assertStringContainsString('This value is not a valid email address', $result->message);
    }
}
