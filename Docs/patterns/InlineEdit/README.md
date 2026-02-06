# Inline Edit Pattern

Edit values directly inline without opening a modal dialog. Uses the Turbo-native pattern where the entire turbo-frame replaces itself.

## Overview

The inline edit pattern allows users to click on a displayed value, edit it in place, and save — all without leaving the current page or opening a modal.

## Quick Start

To make a field inline-editable, you need:

1. **Display template** - wraps value in `<twig:InlineEdit>`
2. **Controller action** - handles display/edit/submit
3. **Include in parent template**

That's it! No custom form types or models needed.

## Usage

### 1. Create Display Template

```twig
{# templates/catalog/manufacturer/_inline_name.html.twig #}
<twig:InlineEdit
    editUrl="{{ path('app_catalog_manufacturer_inline_name', {id: manufacturer.publicId}) }}"
    frameId="inline-edit-manufacturer-{{ manufacturer.publicId }}-name"
    displayClass="text-lg font-semibold text-gray-900 dark:text-white"
>
    {{ manufacturer.name }}
</twig:InlineEdit>
```

### 2. Add Controller Action

```php
use App\Shared\UI\Http\FormFlow\InlineEdit\InlineEditContext;
use App\Shared\UI\Http\FormFlow\InlineEdit\InlineEditFlow;

#[Route('/manufacturer/{id}/inline/name', name: 'app_catalog_manufacturer_inline_name', methods: ['GET', 'POST'])]
public function inlineName(
    Request $request,
    #[ValueResolver('public_id')] Manufacturer $manufacturer,
    InlineEditFlow $flow,
): Response {
    return $flow->handleField(
        request: $request,
        value: $manufacturer->getName(),
        onSave: fn($value) => $manufacturer->update((string) $value, $manufacturer->isActive()),
        context: InlineEditContext::create(
            frameId: 'inline-edit-manufacturer-' . $manufacturer->getPublicId() . '-name',
            displayTemplate: 'catalog/manufacturer/_inline_name.html.twig',
            entity: $manufacturer,
        ),
    );
}
```

Entity-level validation (e.g., `#[Assert\NotBlank]` on the property) is used automatically.
For additional form-level constraints, pass `formOptions: ['constraints' => [...]]`.

### 3. Include in Parent Template

```twig
{# templates/catalog/manufacturer/_manufacturer_card.html.twig #}
<twig:Card title="Manufacturer">
    <p class="mb-3">
        {{ include('catalog/manufacturer/_inline_name.html.twig', {manufacturer: manufacturer}) }}
    </p>
</twig:Card>
```

## How It Works

```
┌─────────────────────────────────────────────────────────────────┐
│  Display Mode (turbo-frame)                                      │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │  "Acme Corp"  [✏️]  ← Click anywhere to edit                ││
│  └─────────────────────────────────────────────────────────────┘│
│                              │                                   │
│                         click                                    │
│                              ▼                                   │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │  Acme Corp [✓] [✗]  ← Seamless input with underline         ││
│  └─────────────────────────────────────────────────────────────┘│
│                              │                                   │
│     Enter / click outside    │    Escape / click ✗              │
│              ▼               ▼                                   │
│           Save            Cancel                                 │
└─────────────────────────────────────────────────────────────────┘
```

## Features

- **Click anywhere** on the value to edit
- **Auto-grow** input expands as you type
- **Click outside** or **Enter** to save
- **Escape** or **✗** to cancel
- **Subtle underline** indicates edit mode
- **Preloads on hover** for instant response

## Keyboard Shortcuts

| Input Type | Submit | Cancel |
|------------|--------|--------|
| Text/Number | Enter | Escape |
| Textarea | Cmd/Ctrl+Enter | Escape |

## API Reference

### `InlineEditFlow::handleField()`

Simple handler for most use cases:

```php
$flow->handleField(
    request: $request,
    value: $currentValue,           // Current field value
    onSave: fn($value) => ...,      // Callback to save new value
    context: InlineEditContext::create(...),
    formOptions: [                   // Optional
        'constraints' => [...],      // Symfony validation constraints
        'field_type' => TextType::class,  // Form field type
        'placeholder' => 'Enter...',
    ],
);
```

### `InlineEditFlow::handle()`

Full handler for complex cases with custom form types:

```php
$flow->handle(
    request: $request,
    formType: CustomFormType::class,
    data: $formModel,
    mapper: fn($form) => new Command(...),
    handler: $commandHandler,
    context: InlineEditContext::create(...),
);
```

### `InlineEditContext::create()`

| Parameter | Type | Description |
|-----------|------|-------------|
| `frameId` | string | Unique turbo-frame ID |
| `displayTemplate` | string | Template path for display mode |
| `entity` | object | The entity being edited |
| `cancelUrl` | string\|null | URL for cancel (derived from request if null) |
| `entityVarName` | string\|null | Variable name in template (auto-derived) |
| `displayTemplateVars` | array | Additional template variables |
| `successMessage` | string\|null | Flash message (null to disable) |

### `<twig:InlineEdit>` Component

| Prop | Type | Default | Description |
|------|------|---------|-------------|
| `editUrl` | string | required | URL for the inline edit endpoint |
| `frameId` | string | required | Unique turbo-frame ID |
| `showEditIcon` | bool | `true` | Show edit icon on hover |
| `displayClass` | string | `''` | CSS classes for styling |
| `editIcon` | string | `'mynaui:edit-one'` | Icon name |
| `editIconSize` | string | `'h-4 w-4'` | Icon size classes |

## Form Options

The `formOptions` parameter is optional. Entity-level validation is used automatically
(exceptions from `onSave` are caught and displayed as form errors).

```php
'formOptions' => [
    // Additional validation (optional - entity validation is used by default)
    'constraints' => [
        new Assert\Email(),
        new Assert\Regex('/^[A-Z]/'),
    ],

    // Field type (default: TextType)
    'field_type' => NumberType::class,
    'field_type' => TextareaType::class,
    'field_type' => ChoiceType::class,

    // Field attributes
    'field_attr' => [
        'min' => 0,
        'step' => 0.01,
    ],

    // Placeholder
    'placeholder' => 'Enter value...',
]
```

## Files

```
assets/controllers/
└── inline_edit_controller.js      # ~80 lines

src/Shared/UI/
├── Http/FormFlow/InlineEdit/
│   ├── InlineEditContext.php
│   └── InlineEditFlow.php
├── Http/Form/
│   ├── Model/InlineFieldForm.php  # Generic single-field model
│   └── Type/InlineFieldType.php   # Generic single-field type
└── Twig/Components/
    └── InlineEdit.php

templates/
├── components/
│   └── InlineEdit.html.twig
└── shared/form_flow/
    ├── inline_edit_display.html.twig
    ├── inline_edit_form.html.twig
    └── inline_edit_success.stream.html.twig
```
