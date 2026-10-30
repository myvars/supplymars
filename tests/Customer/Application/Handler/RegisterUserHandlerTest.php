<?php

declare(strict_types=1);

namespace App\Tests\Customer\Application\Handler;

use App\Customer\Application\Command\RegisterUser;
use App\Customer\Application\Handler\RegisterUserHandler;
use App\Tests\Shared\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;

final class RegisterUserHandlerTest extends KernelTestCase
{
    public function testFailsOnEmailInUseWithReadableMessage(): void
    {
        UserFactory::createOne(['email' => 'taken@example.com']);

        $handler = self::getContainer()->get(RegisterUserHandler::class);

        $result = $handler(new RegisterUser(
            fullName: 'New Customer',
            email: 'taken@example.com',
            plainPassword: 'correct-horse-battery',
        ));

        self::assertFalse($result->ok);
        self::assertSame('There is already an account with this email', $result->message);
        UserFactory::assert()->count(1);
    }
}
