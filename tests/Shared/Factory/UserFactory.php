<?php

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
    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#factories-as-services
     *
     * @todo inject services if required
     */
    public function __construct(private readonly UserPasswordHasherInterface $passwordEncoder)
    {
    }

    public static function class(): string
    {
        return User::class;
    }

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#model-factories
     *
     * @todo add your default values here
     */
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

    /**
     * @see https://symfony.com/bundles/ZenstruckFoundryBundle/current/index.html#initialization
     */
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
}
