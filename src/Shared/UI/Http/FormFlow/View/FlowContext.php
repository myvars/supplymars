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
    private ?string $model = null;

    private ?string $template = null;

    private ?FormOperation $operation = null;

    private ?string $successRoute = null;

    private array $successParams = [];

    private bool $allowDelete = false;

    private bool $redirectRefresh = false;

    private int $redirectStatus = 303;

    public static function new(): self
    {
        return new self();
    }

    /** Factory for successRoute convenience. */
    public static function forSuccess(string $route, array $params = []): self
    {
        return new self()->successRoute($route, $params);
    }

    /** Factory for create operation defaults. */
    public static function forCreate(string $model): self
    {
        return self::fromOperation($model, FormOperation::Create);
    }

    /** Factory for update operation defaults. */
    public static function forUpdate(string $model): self
    {
        return self::fromOperation($model, FormOperation::Update);
    }

    /** Factory for delete operation defaults. */
    public static function forDelete(string $model): self
    {
        return self::fromOperation($model, FormOperation::Delete);
    }

    /** Factory for filter operation defaults. */
    public static function forFilter(string $model): self
    {
        return self::fromOperation($model, FormOperation::Filter);
    }

    /** Factory for generic operation defaults. */
    private static function fromOperation(string $model, FormOperation $operation): self
    {
        $self = new self();

        $self->model = $model;
        $self->operation = $operation;
        $self->template = ModelPath::template($model, $operation->value);
        $self->successRoute = sprintf('app_%s_index', ModelPath::route($model));

        return $self;
    }

    public function model(string $model): self
    {
        $this->model = $model;

        return $this;
    }

    public function template(string $template): self
    {
        $this->template = $template;

        return $this;
    }

    public function successRoute(string $route, array $params = []): self
    {
        $this->successRoute = $route;
        $this->successParams = $params;

        return $this;
    }

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

    public function getModel(): ?string
    {
        return $this->model;
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
        if (null === $this->model) {
            throw new \LogicException('Model not configured.');
        }

        if (!$this->operation instanceof FormOperation) {
            throw new \LogicException('Form operation not configured.');
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
