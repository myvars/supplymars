<?php

namespace App\Shared\UI\Http\FormFlow\View;

use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\Routing\Generator\UrlGeneratorInterface;

/**
 * Declarative configuration for a flow run (operation type, success URL, flashes, status, etc.).
 * Used by flows to keep controller calls short and consistent.
 */
final class FlowContext
{
    private ?FlowModel $flowModel = null;

    private ?string $template = null;

    private ?FormOperation $operation = null;

    private ?string $successRoute = null;

    /** @var array<string, mixed> */
    private array $successParams = [];

    private ?FlowRoutes $routes = null;

    private bool $allowDelete = false;

    private bool $redirectRefresh = false;

    private int $redirectStatus = 303;

    public static function new(): self
    {
        return new self();
    }

    /**
     * Factory for successRoute convenience.
     *
     * @param array<string, mixed> $params
     */
    public static function forSuccess(string $route, array $params = []): self
    {
        return self::new()->successRoute($route, $params);
    }

    /**
     * Factory for command operation (no model, just success route).
     *
     * @param array<string, mixed> $params
     */
    public static function forCommand(string $route, array $params = []): self
    {
        $self = new self();
        $self->operation = FormOperation::Command;
        $self->successRoute = $route;
        $self->successParams = $params;

        return $self;
    }

    /** Factory for create operation defaults. */
    public static function forCreate(FlowModel $model): self
    {
        return self::fromOperation($model, FormOperation::Create);
    }

    /** Factory for update operation defaults. */
    public static function forUpdate(FlowModel $model): self
    {
        return self::fromOperation($model, FormOperation::Update);
    }

    /** Factory for delete operation defaults. */
    public static function forDelete(FlowModel $model): self
    {
        $self = self::fromOperation($model, FormOperation::Delete);
        $self->redirectRefresh = true; // Enable smart navigation for deletes

        return $self;
    }

    /** Factory for filter operation defaults. */
    public static function forFilter(FlowModel $model): self
    {
        return self::fromOperation($model, FormOperation::Filter);
    }

    /** Factory for search/index operation defaults. */
    public static function forSearch(FlowModel $model): self
    {
        $self = new self();
        $self->flowModel = $model;
        $self->operation = FormOperation::Index;
        $self->template = $model->template(FormOperation::Index->value);
        $self->routes = $model->routes;

        return $self;
    }

    /** Factory for generic operation defaults. */
    private static function fromOperation(FlowModel $model, FormOperation $operation): self
    {
        $self = new self();
        $self->flowModel = $model;
        $self->operation = $operation;
        $self->template = $model->template($operation->value);
        $self->routes = $model->routes;
        $self->successRoute = $model->defaultSuccessRoute;

        return $self;
    }

    public function model(FlowModel $model): self
    {
        $this->flowModel = $model;

        return $this;
    }

    public function getFlowModel(): ?FlowModel
    {
        return $this->flowModel;
    }

    public function template(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function successRoute(string $route, array $params = []): self
    {
        $this->successRoute = $route;
        $this->successParams = $params;

        return $this;
    }

    /**
     * @param array<string, mixed> $params
     */
    public function successParams(array $params): self
    {
        $this->successParams = $params;

        return $this;
    }

    public function allowDelete(bool $allowDelete): self
    {
        $this->allowDelete = $allowDelete;

        return $this;
    }

    public function redirectOptions(bool $refresh = false, int $status = 303): self
    {
        $this->redirectRefresh = $refresh;
        $this->redirectStatus = $status;

        return $this;
    }

    /** Replace all routes from a new prefix (e.g. "app_catalog_product"). */
    public function routePrefix(string $prefix): self
    {
        $this->routes = FlowRoutes::fromPrefix($prefix);

        return $this;
    }

    public function getRoutes(): ?FlowRoutes
    {
        return $this->routes;
    }

    public function getTemplate(): ?string
    {
        return $this->template;
    }

    public function getOperation(): ?FormOperation
    {
        return $this->operation;
    }

    public function getSuccessRoute(): ?string
    {
        return $this->successRoute;
    }

    /**
     * @return array<string, mixed>
     */
    public function getSuccessParams(): array
    {
        return $this->successParams;
    }

    public function isAllowDelete(): bool
    {
        return $this->allowDelete;
    }

    public function isRedirectRefresh(): bool
    {
        return $this->redirectRefresh;
    }

    public function getRedirectStatus(): int
    {
        return $this->redirectStatus;
    }

    /**
     * Validate that required properties are set.
     *
     * @throws \LogicException if validation fails
     */
    public function validate(): void
    {
        if (!$this->flowModel instanceof FlowModel) {
            throw new \LogicException('Model not configured.');
        }

        if (!$this->operation instanceof FormOperation) {
            throw new \LogicException('Form operation not configured.');
        }
    }

    /**
     * Validate for command flows (only needs success route).
     *
     * @throws \LogicException if successRoute not configured
     */
    public function validateForCommand(): void
    {
        if (null === $this->successRoute) {
            throw new \LogicException('Success route not configured.');
        }
    }

    /**
     * Validate for search flows (needs model only).
     *
     * @throws \LogicException if model not configured
     */
    public function validateForSearch(): void
    {
        if (!$this->flowModel instanceof FlowModel) {
            throw new \LogicException('Model not configured.');
        }
    }

    /** Resolve the success URL for redirects. */
    public function resolveSuccessUrl(Request $request, UrlGeneratorInterface $urls): string
    {
        if ($this->successRoute) {
            return $urls->generate($this->successRoute, $this->successParams);
        }

        $referer = $request->headers->get('referer');

        return ($referer !== null && $referer !== '') ? $referer : $request->getPathInfo();
    }

    /** Resolve the back URL for back links. */
    public function resolveBackUrl(Request $request): ?string
    {
        $referer = $request->headers->get('referer');

        return ($referer !== null && $referer !== '') ? $referer : null;
    }
}
