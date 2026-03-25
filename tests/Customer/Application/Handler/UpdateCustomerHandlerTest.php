<?php

namespace App\Tests\Customer\Application\Handler;

use App\Customer\Application\Command\UpdateCustomer;
use App\Customer\Application\Handler\UpdateCustomerHandler;
use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\Domain\Repository\UserRepository;
use App\Customer\Infrastructure\Mailer\MailerHelper;
use App\Shared\Application\FlusherInterface;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Validator\Validator\ValidatorInterface;
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
        $handler = $this->handlerWithSecurity(isSuperAdmin: true);

        $command = new UpdateCustomer(
            id: $user->getPublicId(),
            fullName: 'Updated User',
            email: 'updated@example.com',
            isVerified: false,
            isStaff: false,
        );

        $result = ($handler)($command);
        self::assertTrue($result->ok);

        $persisted = $this->users->getByPublicId($user->getPublicId());
        self::assertSame('Updated User', $persisted->getFullName());
        self::assertSame('updated@example.com', $persisted->getEmail());
        self::assertFalse($persisted->isVerified());
        self::assertFalse($persisted->isStaff());
        self::assertFalse($persisted->isAdmin());
    }

    public function testGrantingStaffSendsAdminAccessEmail(): void
    {
        $user = UserFactory::new()->create();
        self::assertFalse($user->isStaff());

        $command = new UpdateCustomer(
            id: $user->getPublicId(),
            fullName: $user->getFullName(),
            email: $user->getEmail(),
            isVerified: $user->isVerified(),
            isStaff: true,
        );

        $result = ($this->handler)($command);
        self::assertTrue($result->ok);

        $persisted = $this->users->getByPublicId($user->getPublicId());
        self::assertTrue($persisted->isStaff());
        self::assertTrue($persisted->isAdmin());

        // Email dispatch verified via Messenger async transport.
        // Visual verification: symfony console app:send-test-emails
        $transport = self::getContainer()->get('messenger.transport.async');
        $messages = iterator_to_array($transport->get());
        self::assertNotEmpty($messages, 'Admin access email should be dispatched to async transport');
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

    private function handlerWithSecurity(bool $isSuperAdmin): UpdateCustomerHandler
    {
        $security = $this->createMock(Security::class);
        $security->method('isGranted')
            ->with('ROLE_SUPER_ADMIN')
            ->willReturn($isSuperAdmin);

        return new UpdateCustomerHandler(
            self::getContainer()->get(UserRepository::class),
            self::getContainer()->get(FlusherInterface::class),
            self::getContainer()->get(ValidatorInterface::class),
            self::getContainer()->get(MailerHelper::class),
            $security,
        );
    }

    public function testNonSuperAdminCannotEditStaffAccount(): void
    {
        $staffUser = UserFactory::new()->asStaff()->create();
        $handler = $this->handlerWithSecurity(isSuperAdmin: false);

        $command = new UpdateCustomer(
            id: $staffUser->getPublicId(),
            fullName: 'Hacked Name',
            email: 'hacked@example.com',
            isVerified: true,
            isStaff: true,
        );

        $result = ($handler)($command);

        self::assertFalse($result->ok);
        self::assertStringContainsString('Staff accounts cannot be modified', $result->message);

        $persisted = $this->users->getByPublicId($staffUser->getPublicId());
        self::assertSame($staffUser->getFullName(), $persisted->getFullName());
    }

    public function testNonSuperAdminCanEditNonStaffAccount(): void
    {
        $regularUser = UserFactory::createOne(['fullName' => 'Regular User']);
        $handler = $this->handlerWithSecurity(isSuperAdmin: false);

        $command = new UpdateCustomer(
            id: $regularUser->getPublicId(),
            fullName: 'Updated Name',
            email: $regularUser->getEmail(),
            isVerified: $regularUser->isVerified(),
            isStaff: false,
        );

        $result = ($handler)($command);

        self::assertTrue($result->ok);

        $persisted = $this->users->getByPublicId($regularUser->getPublicId());
        self::assertSame('Updated Name', $persisted->getFullName());
    }

    public function testSuperAdminCanEditStaffAccount(): void
    {
        $staffUser = UserFactory::new()->asStaff()->create();
        $handler = $this->handlerWithSecurity(isSuperAdmin: true);

        $command = new UpdateCustomer(
            id: $staffUser->getPublicId(),
            fullName: 'Updated Staff',
            email: $staffUser->getEmail(),
            isVerified: $staffUser->isVerified(),
            isStaff: true,
        );

        $result = ($handler)($command);

        self::assertTrue($result->ok);

        $persisted = $this->users->getByPublicId($staffUser->getPublicId());
        self::assertSame('Updated Staff', $persisted->getFullName());
    }
}
