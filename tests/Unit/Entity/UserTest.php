<?php

namespace App\Tests\Unit\Entity;

use App\Entity\User;
use PHPUnit\Framework\TestCase;

class UserTest extends TestCase
{
    public function testSettersAndGetters(): void
    {
        $user = new User();
        $user->setEmail('test@example.com')
            ->setFullName('John Doe')
            ->setPassword('password123')
            ->setIsVerified(true)
            ->setIsStaff(false);

        $this->assertEquals('test@example.com', $user->getEmail());
        $this->assertEquals('John Doe', $user->getFullName());
        $this->assertEquals('password123', $user->getPassword());
        $this->assertTrue($user->isVerified());
        $this->assertFalse($user->isStaff());
    }
}