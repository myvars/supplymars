<?php

namespace App\Tests\Shared\Infrastructure\Security;

use App\Customer\Domain\Model\User\User;
use App\Shared\Infrastructure\Security\DefaultUserAuthenticator;
use PHPUnit\Framework\TestCase;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

final class DefaultUserAuthenticatorTest extends TestCase
{
    public function testEnsureAuthenticatedDoesNothingWhenAlreadyAuthenticated(): void
    {
        $security = $this->createMock(Security::class);
        $security->method('getUser')->willReturn($this->createStub(User::class));
        $security->expects(self::never())->method('login');

        $userProvider = $this->createStub(UserProviderInterface::class);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::never())->method('setToken');

        $requestStack = $this->createStub(RequestStack::class);

        $authenticator = new DefaultUserAuthenticator(
            $security,
            $userProvider,
            $tokenStorage,
            $requestStack
        );

        $authenticator->ensureAuthenticated();
    }

    public function testLoginSetsTokenWhenNoHttpRequest(): void
    {
        $security = $this->createMock(Security::class);
        $security->expects(self::never())->method('login');

        $userProvider = $this->createStub(UserProviderInterface::class);
        $domainUser = $this->createStub(User::class);
        $domainUser->method('getRoles')->willReturn([]);
        $userProvider->method('loadUserByIdentifier')->willReturn($domainUser);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage
            ->expects(self::once())
            ->method('setToken')
            ->with(self::callback(fn ($token): bool => $token instanceof PostAuthenticationToken
                && $token->getUser() === $domainUser));

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getMainRequest')->willReturn(null);

        $authenticator = new DefaultUserAuthenticator(
            $security,
            $userProvider,
            $tokenStorage,
            $requestStack
        );

        $authenticator->login();
    }

    public function testLoginUsesSecurityLoginWhenHttpRequestExists(): void
    {
        $security = $this->createMock(Security::class);

        $userProvider = $this->createStub(UserProviderInterface::class);
        $domainUser = $this->createStub(User::class);
        $domainUser->method('getRoles')->willReturn(['ROLE_USER']);
        $userProvider->method('loadUserByIdentifier')->willReturn($domainUser);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::never())->method('setToken');

        $requestStack = new RequestStack();
        $requestStack->push(new Request());

        $security->expects(self::once())
            ->method('login')
            ->with($domainUser, 'main', self::isNull());

        $authenticator = new DefaultUserAuthenticator(
            $security,
            $userProvider,
            $tokenStorage,
            $requestStack
        );

        $authenticator->login();
    }

    public function testLoginThrowsWhenLoadedUserIsNotDomainUser(): void
    {
        $security = $this->createStub(Security::class);

        $userProvider = $this->createStub(UserProviderInterface::class);
        $notDomainUser = $this->createStub(UserInterface::class); // not instance of domain User
        $userProvider->method('loadUserByIdentifier')->willReturn($notDomainUser);

        $tokenStorage = $this->createStub(TokenStorageInterface::class);

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getMainRequest')->willReturn(null);

        $authenticator = new DefaultUserAuthenticator(
            $security,
            $userProvider,
            $tokenStorage,
            $requestStack
        );

        $this->expectException(\RuntimeException::class);
        $this->expectExceptionMessage('Default user is not a valid user.');
        $authenticator->login();
    }

    public function testEnsureAuthenticatedTriggersLoginWhenNoUser(): void
    {
        $security = $this->createStub(Security::class);
        $security->method('getUser')->willReturn(null);

        $userProvider = $this->createMock(UserProviderInterface::class);
        $domainUser = $this->createStub(User::class);
        $domainUser->method('getRoles')->willReturn([]);
        $userProvider->expects(self::once())
            ->method('loadUserByIdentifier')
            ->willReturn($domainUser);

        $tokenStorage = $this->createMock(TokenStorageInterface::class);
        $tokenStorage->expects(self::once())
            ->method('setToken')
            ->with(self::isInstanceOf(PostAuthenticationToken::class));

        $requestStack = $this->createStub(RequestStack::class);
        $requestStack->method('getMainRequest')->willReturn(null);

        $authenticator = new DefaultUserAuthenticator(
            $security,
            $userProvider,
            $tokenStorage,
            $requestStack
        );

        $authenticator->ensureAuthenticated();
    }
}
