<?php

namespace App\Tests\Integration\Entity;

use App\Factory\ResetPasswordRequestFactory;
use App\Factory\UserFactory;
use Symfony\Bundle\FrameworkBundle\Test\KernelTestCase;
use Symfony\Component\Validator\Validator\ValidatorInterface;
use Zenstruck\Foundry\Test\Factories;

class ResetPasswordRequestIntegrationTest extends KernelTestCase
{
    use Factories;

    private ValidatorInterface $validator;

    protected function setUp(): void
    {
        self::bootKernel();
        $this->validator = static::getContainer()->get(ValidatorInterface::class);
    }

    public function testValidResetPasswordRequest(): void
    {
        $user = UserFactory::createOne();

        $resetPasswordRequest = ResetPasswordRequestFactory::createOne([
            'user' => $user,
            'expiresAt' => new \DateTimeImmutable('2023-01-01'),
            'selector' => 'selector',
            'hashedToken' => 'hashedToken',
        ]);

        $errors = $this->validator->validate($resetPasswordRequest);
        $this->assertCount(0, $errors);
    }

    public function testUserIsRequired(): void
    {
        $resetPasswordRequest = ResetPasswordRequestFactory::new()->withoutPersisting()->create(['user' => null]);

        $violations = $this->validator->validate($resetPasswordRequest);
        $this->assertSame('User should not be null', $violations[0]->getMessage());
    }

    public function testResetPasswordRequestPersistence(): void
    {
        $user = UserFactory::createOne();

        ResetPasswordRequestFactory::createOne([
            'user' => $user,
            'expiresAt' => new \DateTimeImmutable('2023-01-01'),
            'selector' => 'selector',
            'hashedToken' => 'hashedToken',
        ]);

        $persistedResetPasswordRequest = ResetPasswordRequestFactory::repository()->findOneBy([
            'selector' => 'selector'
        ]);

        $this->assertEquals('hashedToken', $persistedResetPasswordRequest->getHashedToken());
    }
}