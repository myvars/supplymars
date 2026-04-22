<?php

declare(strict_types=1);

namespace App\Shared\Infrastructure\Security;

use Symfony\Component\HttpFoundation\JsonResponse;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpFoundation\Response;
use Symfony\Component\Security\Core\Exception\AuthenticationException;
use Symfony\Component\Security\Http\Authentication\AuthenticationFailureHandlerInterface;
use Symfony\Component\Security\Http\EntryPoint\AuthenticationEntryPointInterface;

final class ApiAuthenticationFailureHandler implements AuthenticationFailureHandlerInterface, AuthenticationEntryPointInterface
{
    public function start(Request $request, ?AuthenticationException $authException = null): JsonResponse
    {
        return new JsonResponse([
            'type' => 'about:blank',
            'title' => Response::$statusTexts[401],
            'status' => 401,
            'detail' => 'Authentication required.',
        ], 401, ['Content-Type' => 'application/problem+json']);
    }

    public function onAuthenticationFailure(Request $request, AuthenticationException $exception): JsonResponse
    {
        return new JsonResponse([
            'type' => 'about:blank',
            'title' => Response::$statusTexts[401],
            'status' => 401,
            'detail' => 'Invalid API token.',
        ], 401, ['Content-Type' => 'application/problem+json']);
    }
}
