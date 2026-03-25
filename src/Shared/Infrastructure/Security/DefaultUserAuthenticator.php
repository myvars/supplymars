<?php

namespace App\Shared\Infrastructure\Security;

use App\Customer\Domain\Model\User\User;
use Symfony\Bundle\SecurityBundle\Security;
use Symfony\Component\DependencyInjection\Attribute\Autowire;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\RequestStack;
use Symfony\Component\Security\Core\Authentication\Token\Storage\TokenStorageInterface;
use Symfony\Component\Security\Core\User\UserProviderInterface;
use Symfony\Component\Security\Http\Authenticator\Token\PostAuthenticationToken;

final readonly class DefaultUserAuthenticator
{
    public const string DEFAULT_FIREWALL = 'main';

    /**
     * @param UserProviderInterface<User> $userProvider
     */
    public function __construct(
        private Security $security,
        private UserProviderInterface $userProvider,
        private TokenStorageInterface $tokenStorage,
        private RequestStack $requestStack,
        #[Autowire(env: 'DEFAULT_STAFF_EMAIL')]
        private string $defaultStaffEmail,
        private string $firewall = self::DEFAULT_FIREWALL,
    ) {
    }

    public function getDefaultEmail(): string
    {
        return $this->defaultStaffEmail;
    }

    /**
     * If there is no authenticated domain user, log in the default user.
     */
    public function ensureAuthenticated(): void
    {
        if ($this->security->getUser() instanceof User) {
            return;
        }

        $this->login();
    }

    /**
     * Force login as the configured default user.
     */
    public function login(): void
    {
        $user = $this->userProvider->loadUserByIdentifier($this->defaultStaffEmail);
        if (!$user instanceof User) {
            throw new \RuntimeException('Default user is not a valid user.');
        }

        if (!$this->requestStack->getMainRequest() instanceof Request) {
            $token = new PostAuthenticationToken($user, $this->firewall, $user->getRoles());
            $this->tokenStorage->setToken($token);

            return;
        }

        // HTTP request available -> use the official login flow
        $this->security->login($user, $this->firewall);
    }
}
