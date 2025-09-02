<?php

namespace App\EventListener;

use App\ValueObject\AbstractUlidId;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag('app.public_id_resolver')]
interface PublicIdResolver
{
    /** Return the VO class this resolver supports, e.g. CustomerOrderPublicId::class */
    public static function supports(): string;

    /** Resolve legacy int id for this public id (or null if none). */
    public function resolve(AbstractUlidId $publicId): ?int;
}
