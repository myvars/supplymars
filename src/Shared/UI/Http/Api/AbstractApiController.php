<?php

namespace App\Shared\UI\Http\Api;

use App\Shared\Application\Identity\PublicIdResolverRegistry;
use App\Shared\Application\Result;
use App\Shared\Domain\ValueObject\AbstractUlidId;
use Symfony\Bundle\FrameworkBundle\Controller\AbstractController;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;

abstract class AbstractApiController extends AbstractController
{
    public function __construct(
        private readonly PublicIdResolverRegistry $publicIdResolver,
    ) {
    }

    protected function handleResult(
        Result $result,
        int $successStatus = 200,
        ApiResourceInterface|callable|null $onSuccess = null,
    ): JsonResponse {
        if (!$result->ok) {
            return ApiResponse::error($result->message ?? 'Operation failed.', 422);
        }

        $data = match (true) {
            $onSuccess instanceof ApiResourceInterface => $onSuccess->toArray(),
            $onSuccess !== null => $onSuccess($result),
            default => ['message' => $result->message],
        };

        return ApiResponse::item($data, $successStatus);
    }

    /**
     * @param class-string<AbstractUlidId> $publicIdClass
     */
    protected function resolveFilterId(Request $request, string $param, string $publicIdClass): ?int
    {
        if (!$request->query->has($param)) {
            return null;
        }

        return $this->publicIdResolver->resolve(
            $publicIdClass::fromString($request->query->getString($param))
        );
    }
}
