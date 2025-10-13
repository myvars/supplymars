<?php

namespace App\Tests\Customer\Unit;

use App\Customer\Domain\Model\User\ResetPasswordRequest;
use App\Customer\Domain\Model\User\User;
use PHPUnit\Framework\TestCase;

class ResetPasswordRequestTest extends TestCase
{
    public function testCreate(): void
    {
        $user = $this->createMock(User::class);
        $expiresAt = new \DateTimeImmutable('2023-01-01');
        $selector = 'selector';
        $hashedToken = 'hashedToken';

        $resetPasswordRequest = new ResetPasswordRequest($user, $expiresAt, $selector, $hashedToken);

        $this->assertEquals($user, $resetPasswordRequest->getUser());
        $this->assertEquals($expiresAt, $resetPasswordRequest->getExpiresAt());
        $this->assertEquals($hashedToken, $resetPasswordRequest->getHashedToken());
    }
}
