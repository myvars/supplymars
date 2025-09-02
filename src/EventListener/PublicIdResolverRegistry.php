<?php

namespace App\EventListener;

use App\ValueObject\AbstractUlidId;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Component\DependencyInjection\ContainerInterface;

final class PublicIdResolverRegistry
{
    /**
     * @param ContainerInterface $resolvers keyed by supports() return
     */
    public function __construct(
        #[AutowireLocator('app.public_id_resolver', defaultIndexMethod: 'supports')]
        private readonly ContainerInterface $resolvers,
    ) {
    }

    /** simple in-request cache */
    private array $cache = []; // ["VOClass|value" => int|null]

    public function resolve(AbstractUlidId $publicId): ?int
    {
        $key = $publicId::class.'|'.$publicId->value();
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        /** @var PublicIdResolver|null $resolver */
        $resolver = $this->resolvers[$publicId::class] ?? null;

        return $this->cache[$key] = $resolver?->resolve($publicId);
    }
}
