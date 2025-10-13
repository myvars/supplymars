<?php

namespace App\Shared\UI\Http\FormFlow\View;

/**
 * Small immutable bag of Twig variables used across flow templates..
 */
final readonly class TemplateContext
{
    public function __construct(
        public string $flowModel,
        public string $flowRoute,
        public string $flowPath,
        public string $flowOperation,
        public string $template,
    ) {
    }

    /**
     * Create a context for a given model and operation; resolves defaults for template names.
     */
    public static function from(string $model, string $operation, ?string $template = null): self
    {
        return new self(
            flowModel: ucfirst(ModelPath::model($model)),
            flowRoute: ModelPath::route($model),
            flowPath: ModelPath::path($model),
            flowOperation: $operation,
            template: $template ?? ModelPath::template($model, $operation),
        );
    }

    /**
     * Export keys expected by Twig templates.
     */
    public function toArray(): array
    {
        return [
            'flowModel' => $this->flowModel,
            'flowRoute' => $this->flowRoute,
            'flowPath' => $this->flowPath,
            'flowOperation' => $this->flowOperation,
            'template' => $this->template,
        ];
    }
}
