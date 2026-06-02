<?php

declare(strict_types=1);

namespace App\Shared\Application\Identity;

use App\Shared\Domain\ValueObject\AbstractUlidId;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.public_id_resolver')]
interface PublicIdResolverInterface
{
    /** Resolve legacy int id for this public id (or null if none). */
    public function resolve(AbstractUlidId $publicId): ?int;
}
