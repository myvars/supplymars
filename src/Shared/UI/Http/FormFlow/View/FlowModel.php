<?php

namespace App\Shared\UI\Http\FormFlow\View;

/**
 * Typed value object replacing the MODEL string constant in controllers.
 *
 * Derives display name, template directory, route prefix, and default success
 * route from explicit values, with convention-based factories for the common cases.
 */
final readonly class FlowModel
{
    public const string BASE_TEMPLATE = 'shared/form_flow/base.html.twig';

    public function __construct(
        public string $displayName,
        public string $templateDir,
        public FlowRoutes $routes,
        public string $defaultSuccessRoute,
    ) {
    }

    /**
     * Entity within a bounded context.
     *
     * Example: FlowModel::create('catalog', 'manufacturer')
     */
    public static function create(string $context, string $entity, ?string $displayName = null): self
    {
        $templateDir = $context . '/' . $entity;
        $routePrefix = 'app_' . $context . '_' . $entity;

        return new self(
            displayName: $displayName ?? self::titleCase($entity),
            templateDir: $templateDir,
            routes: FlowRoutes::fromPrefix($routePrefix),
            defaultSuccessRoute: $routePrefix . '_index',
        );
    }

    /**
     * Entity without bounded context.
     *
     * Example: FlowModel::simple('customer')
     */
    public static function simple(string $entity, ?string $displayName = null): self
    {
        $routePrefix = 'app_' . $entity;

        return new self(
            displayName: $displayName ?? self::titleCase($entity),
            templateDir: $entity,
            routes: FlowRoutes::fromPrefix($routePrefix),
            defaultSuccessRoute: $routePrefix . '_index',
        );
    }

    /** Return copy with different display name. */
    public function withDisplayName(string $displayName): self
    {
        return new self(
            displayName: $displayName,
            templateDir: $this->templateDir,
            routes: $this->routes,
            defaultSuccessRoute: $this->defaultSuccessRoute,
        );
    }

    /**
     * Derive template path for an operation.
     *
     * Example: 'catalog/manufacturer' + 'create' -> 'catalog/manufacturer/create.html.twig'
     */
    public function template(string $operation): string
    {
        return $this->templateDir . '/' . $operation . '.html.twig';
    }

    /** Convert snake_case entity name to Title Case. */
    private static function titleCase(string $entity): string
    {
        return mb_convert_case(str_replace('_', ' ', $entity), MB_CASE_TITLE);
    }
}
