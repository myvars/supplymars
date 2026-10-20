<?php

declare(strict_types=1);

namespace App\Shared\Application\Identity;

use App\Shared\Domain\ValueObject\AbstractUlidId;
use Psr\Container\ContainerInterface;
use Symfony\Component\DependencyInjection\Attribute\AutowireLocator;
use Symfony\Contracts\Service\ResetInterface;

final class PublicIdResolverRegistry implements ResetInterface
{
    /**
     * @param ContainerInterface $resolvers keyed by each resolver's #[AsTaggedItem] index (the VO class)
     */
    public function __construct(
        #[AutowireLocator('app.public_id_resolver')]
        private readonly ContainerInterface $resolvers,
    ) {
    }

    /**
     * Misses are cached too. Emptied by reset(), which the container calls between Messenger
     * messages and between requests in a worker, so a result never outlives one request or message.
     *
     * @var array<string, int|null> ["VOClass|value" => int|null]
     */
    private array $cache = [];

    public function resolve(AbstractUlidId $publicId): ?int
    {
        $key = $publicId::class . '|' . $publicId->value();
        if (array_key_exists($key, $this->cache)) {
            return $this->cache[$key];
        }

        /** @var PublicIdResolverInterface|null $resolver */
        $resolver = $this->resolvers->get($publicId::class) ?? null;

        return $this->cache[$key] = $resolver?->resolve($publicId);
    }

    public function reset(): void
    {
        $this->cache = [];
    }
}
