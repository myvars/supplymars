<?php

namespace App\Tests\Unit\Entity;

use App\Entity\ResetPasswordRequest;
use App\Entity\User;
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