<?php

declare(strict_types=1);

namespace App\Shared\UI\Http\ValueResolver;

use Doctrine\Persistence\ManagerRegistry;
use Doctrine\Persistence\ObjectManager;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;
use Symfony\Component\Uid\Ulid;

#[AsTargetedValueResolver('public_id')]
final readonly class PublicIdResolver implements ValueResolverInterface
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    /**
     * @return iterable<object>
     */
    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $class = $argument->getType();
        if (!$class || !class_exists($class)) {
            return [];
        }

        $em = $this->doctrine->getManagerForClass($class);
        if (!$em instanceof ObjectManager) {
            return [];
        }

        $id = $request->attributes->get('id');
        if ($id === null) {
            return [];
        }

        // A malformed id matches nothing; querying the ulid column with it would throw
        $entity = Ulid::isValid((string) $id, Ulid::FORMAT_BASE_32)
            ? $em->getRepository($class)->findOneBy(['publicId' => (string) $id])
            : null;

        if ($entity === null) {
            $short = new \ReflectionClass($class)->getShortName();
            throw new NotFoundHttpException(sprintf('%s "%s" not found.', $short, $id));
        }

        return [$entity];
    }
}
