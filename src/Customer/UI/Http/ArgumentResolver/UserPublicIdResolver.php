<?php

namespace App\Customer\UI\Http\ArgumentResolver;

use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use App\Shared\Application\Identity\AbstractPublicIdResolver;

final readonly class UserPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(UserDoctrineRepository $repository)
    {
        parent::__construct($repository);
    }

    public static function supports(): string
    {
        return UserPublicId::class;
    }
}
