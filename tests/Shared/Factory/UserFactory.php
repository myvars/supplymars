<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Customer\Domain\Model\User\User;
use Symfony\Component\PasswordHasher\Hasher\UserPasswordHasherInterface;
use Zenstruck\Foundry\Object\Instantiator;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<User>
 */
final class UserFactory extends PersistentObjectFactory
{
    public function __construct(private readonly UserPasswordHasherInterface $passwordEncoder)
    {
    }

    public static function class(): string
    {
        return User::class;
    }

    protected function defaults(): array
    {
        return [
            'fullName' => self::faker()->firstName() . ' ' . self::faker()->lastName(),
            'email' => self::faker()->unique()->safeEmail(),
            'isVerified' => true,
            'isStaff' => false,
            'password' => self::faker()->password(),
        ];
    }

    #[\Override]
    protected function initialize(): static
    {
        return $this
            ->instantiateWith(
                Instantiator::namedConstructor('create')
            )
            ->afterInstantiate(function (User $user): void {
                if ('' !== $user->getPassword() && '0' !== $user->getPassword()) {
                    $user->setPassword($this->passwordEncoder->hashPassword($user, $user->getPassword()));
                }
            });
    }

    public function asStaff(): self
    {
        return $this
            ->with(['fullName' => 'Staff Member'])
            ->afterInstantiate(function (User $user): void {
                $user->setStaff(true);
            });
    }

    public function asSuperAdmin(): self
    {
        return $this
            ->with(['fullName' => 'Super Admin'])
            ->afterInstantiate(function (User $user): void {
                $user->setStaff(true);
                $user->setRoles(['ROLE_ADMIN', 'ROLE_SUPER_ADMIN']);
            });
    }

    /**
     * Stores the hash of $token; send the plain $token as the Bearer token.
     */
    public function withApiToken(string $token = 'test-api-token'): self
    {
        return $this->afterInstantiate(function (User $user) use ($token): void {
            $user->setApiToken($token);
        });
    }
}
