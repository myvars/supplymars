<?php

namespace App\Shared\UI\Http\Api;

use Pagerfanta\Pagerfanta;
use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;

final class ApiResponse
{
    /**
     * @param array<string, mixed> $data
     */
    public static function item(array $data, int $status = 200): JsonResponse
    {
        return new JsonResponse(['data' => $data], $status);
    }

    /**
     * @param Pagerfanta<mixed>                           $pager
     * @param class-string<ApiResourceInterface>|callable $resource
     * @param array<string, mixed>                        $queryParams
     */
    public static function collection(
        Pagerfanta $pager,
        string|callable $resource,
        ?Request $request = null,
        string $baseUrl = '',
        array $queryParams = [],
    ): JsonResponse {
        if (\is_string($resource)) {
            $transformer = static fn (object $entity): array => $resource::fromEntity($entity)->toArray();
        } else {
            $transformer = $resource;
        }

        if ($request instanceof Request && $baseUrl === '') {
            $baseUrl = $request->getSchemeAndHttpHost() . $request->getBaseUrl() . $request->getPathInfo();
            $queryParams = $request->query->all();
        }

        $data = [];
        foreach ($pager->getCurrentPageResults() as $entity) {
            $data[] = $transformer($entity);
        }

        $response = [
            'data' => $data,
            'meta' => [
                'page' => $pager->getCurrentPage(),
                'limit' => $pager->getMaxPerPage(),
                'total' => $pager->getNbResults(),
            ],
        ];

        if ($baseUrl !== '') {
            $page = $pager->getCurrentPage();
            $limit = $pager->getMaxPerPage();

            $response['links'] = [
                'self' => self::buildUrl($baseUrl, $queryParams, $page, $limit),
                'next' => $pager->hasNextPage()
                    ? self::buildUrl($baseUrl, $queryParams, $pager->getNextPage(), $limit)
                    : null,
                'prev' => $pager->hasPreviousPage()
                    ? self::buildUrl($baseUrl, $queryParams, $pager->getPreviousPage(), $limit)
                    : null,
            ];
        }

        return new JsonResponse($response);
    }

    public static function error(string $detail, int $status = 400): JsonResponse
    {
        return new JsonResponse([
            'type' => 'about:blank',
            'title' => Response::$statusTexts[$status] ?? 'Error',
            'status' => $status,
            'detail' => $detail,
        ], $status, ['Content-Type' => 'application/problem+json']);
    }

    public static function noContent(): JsonResponse
    {
        return new JsonResponse(null, 204);
    }

    /**
     * @param array<string, mixed> $queryParams
     */
    private static function buildUrl(string $baseUrl, array $queryParams, int $page, int $limit): string
    {
        $params = $queryParams;
        $params['page'] = $page;
        $params['limit'] = $limit;

        return $baseUrl . '?' . http_build_query($params);
    }
}
