<?php

namespace App\Shared\UI\Http\ValueResolver;

use Doctrine\Persistence\ManagerRegistry;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Attribute\AsTargetedValueResolver;
use Symfony\Component\HttpKernel\Controller\ValueResolverInterface;
use Symfony\Component\HttpKernel\ControllerMetadata\ArgumentMetadata;
use Symfony\Component\HttpKernel\Exception\NotFoundHttpException;

#[AsTargetedValueResolver('public_id')]
final readonly class PublicIdResolver implements ValueResolverInterface
{
    public function __construct(private ManagerRegistry $doctrine)
    {
    }

    public function resolve(Request $request, ArgumentMetadata $argument): iterable
    {
        $class = $argument->getType();
        if (!$class || !class_exists($class)) {
            return [];
        }

        $em = $this->doctrine->getManagerForClass($class);
        if ($em === null) {
            return [];
        }

        $id = $request->attributes->get('id');
        if ($id === null) {
            return [];
        }

        $repo = $em->getRepository($class);
        $entity = $repo->findOneBy(['publicId' => (string) $id]);

        if ($entity === null) {
            $short = new \ReflectionClass($class)->getShortName();
            throw new NotFoundHttpException(sprintf('%s "%s" not found.', $short, $id));
        }

        return [$entity];
    }
}
