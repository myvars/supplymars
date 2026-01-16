<?php

namespace App\Shared\UI\Http\FormFlow\View;

use Symfony\Component\String\UnicodeString;

/**
 * Central place for deriving template names, route names, and paths for a model.
 * Keeps routing/path conventions in one location to avoid scattering string logic.
 */
final class ModelPath
{
    public const string BASE_TEMPLATE = 'shared/form_flow/base.html.twig';

    /** Split a resource into bounded context and model name. */
    private static function split(string $resource): array
    {
        // Support "boundedContext/domainModel" syntax.
        return str_contains($resource, '/')
            ? explode('/', $resource, 2)
            : [null, $resource];
    }

    /** Extract the bounded context from a namespaced path. */
    private static function boundedContext(string $resource): ?string
    {
        return self::split($resource)[0];
    }

    /** Extract the short model name from a namespaced path. */
    public static function model(string $resource): string
    {
        return self::split($resource)[1];
    }

    /** Compute the template path segment for a model. */
    public static function path(string $resource): string
    {
        $boundedContext = self::boundedContext($resource);
        $model = self::model($resource);

        return sprintf(
            '%s%s/',
            $boundedContext ? self::snake($boundedContext) . '/' : '',
            self::snake($model)
        );
    }

    /** Optionally derive the default template for an operation. */
    public static function template(string $resource, string $operation): string
    {
        return sprintf('%s%s.html.twig', self::path($resource), $operation);
    }

    /** Compute the route name segment for a model. */
    public static function route(string $resource): string
    {
        $boundedContext = self::boundedContext($resource);
        $model = self::model($resource);

        return self::snake(($boundedContext ? $boundedContext . ' ' : '') . $model);
    }

    /** Convert a resource name to snake_case. */
    public static function snake(string $resource): string
    {
        return new UnicodeString(strtolower($resource))->snake()->toString();
    }
}
