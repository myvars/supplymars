<?php

namespace App\Tests\Customer\Domain;

use App\Customer\Domain\Model\User\User;
use PHPUnit\Framework\TestCase;

final class UserDomainTest extends TestCase
{
    public function testCreateSetsFieldsAndRoles(): void
    {
        $user = User::create(
            fullName: 'Alice Smith',
            email: 'alice@example.com',
            isStaff: true,
            isVerified: true,
        );

        self::assertSame('Alice Smith', $user->getFullName());
        self::assertSame('alice@example.com', $user->getEmail());
        self::assertTrue($user->isVerified());
        self::assertTrue($user->isStaff());
        self::assertTrue($user->isAdmin());
        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testUpdateChangesFieldsAndTogglesAdminRole(): void
    {
        $user = User::create(
            fullName: 'Bob',
            email: 'bob@example.com',
            isStaff: true,
            isVerified: false,
        );
        self::assertTrue($user->isAdmin());

        $user->update(
            fullName: 'Bobby',
            email: 'bobby@example.com',
            isStaff: false,
            isVerified: true,
        );

        self::assertSame('Bobby', $user->getFullName());
        self::assertSame('bobby@example.com', $user->getEmail());
        self::assertTrue($user->isVerified());
        self::assertFalse($user->isStaff());
        self::assertFalse($user->isAdmin());
        self::assertContains('ROLE_USER', $user->getRoles());
        self::assertNotContains('ROLE_ADMIN', $user->getRoles());
    }

    public function testIsDeletableWhenNotAdminAndNoOrders(): void
    {
        $user = User::create(
            fullName: 'Carol',
            email: 'carol@example.com',
            isStaff: false,
            isVerified: true,
        );
        self::assertTrue($user->isDeletable());
    }

    public function testToStringUsesEmail(): void
    {
        $user = User::create(
            fullName: 'Dan',
            email: 'dan@example.com',
            isStaff: false,
            isVerified: false,
        );
        self::assertSame('dan@example.com', (string) $user);
    }
}
