<?php

namespace App\Tests\Shared\Infrastructure\Security;

use App\Customer\Domain\Model\User\User;
use App\Shared\Infrastructure\Security\CurrentUserProvider;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\Security\Core\User\UserInterface;

final class CurrentUserProviderTest extends TestCase
{
    public function testGetReturnsAuthenticatedDomainUser(): void
    {
        $security = $this->createStub(Security::class);
        $domainUser = $this->createStub(User::class);
        $security->method('getUser')->willReturn($domainUser);

        $provider = new CurrentUserProvider($security);

        self::assertSame($domainUser, $provider->get());
    }

    public function testGetThrowsWhenNoAuthenticatedUser(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $provider = new CurrentUserProvider($security);

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('No authenticated user found.');
        $provider->get();
    }

    public function testGetThrowsWhenAuthenticatedIsNotDomainUser(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn($this->createStub(UserInterface::class));

        $provider = new CurrentUserProvider($security);

        $this->expectException(\RuntimeException::class);
        $provider->get();
    }

    public function testHasUserReflectsDomainUserPresence(): void
    {
        $security = $this->createStub(Security::class);

        // Deterministic sequence: domain user -> null -> non-domain user
        $security->method('getUser')->willReturnOnConsecutiveCalls(
            $this->createStub(User::class),
            null,
            $this->createStub(UserInterface::class)
        );

        $provider = new CurrentUserProvider($security);

        self::assertTrue($provider->hasUser());   // domain user
        self::assertFalse($provider->hasUser());  // null
        self::assertFalse($provider->hasUser());  // non-domain user
    }
}
