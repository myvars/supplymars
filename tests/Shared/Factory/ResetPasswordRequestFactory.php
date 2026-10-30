<?php

declare(strict_types=1);

namespace App\Tests\Shared\Factory;

use App\Customer\Domain\Model\User\ResetPasswordRequest;
use App\Customer\Domain\Model\User\User;
use Zenstruck\Foundry\LazyValue;
use Zenstruck\Foundry\Persistence\PersistentObjectFactory;

/**
 * @extends PersistentObjectFactory<ResetPasswordRequest>
 */
final class ResetPasswordRequestFactory extends PersistentObjectFactory
{
    public static function class(): string
    {
        return ResetPasswordRequest::class;
    }

    protected function defaults(): array
    {
        return [
            'expiresAt' => \DateTimeImmutable::createFromMutable(self::faker()->dateTime()),
            'hashedToken' => self::faker()->text(100),
            'selector' => self::faker()->text(20),
            'user' => LazyValue::memoize(fn (): User => UserFactory::createOne()),
        ];
    }
}
