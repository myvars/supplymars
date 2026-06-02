<?php

declare(strict_types=1);

namespace App\Customer\UI\Http\ArgumentResolver;

use App\Customer\Domain\Model\User\UserPublicId;
use App\Customer\Infrastructure\Persistence\Doctrine\UserDoctrineRepository;
use App\Shared\Application\Identity\AbstractPublicIdResolver;
use Symfony\Component\DependencyInjection\Attribute\AsTaggedItem;

#[AsTaggedItem(index: UserPublicId::class)]
final readonly class UserPublicIdResolver extends AbstractPublicIdResolver
{
    public function __construct(UserDoctrineRepository $repository)
    {
        parent::__construct($repository);
    }
}
