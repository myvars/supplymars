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
│  Display Mode (turbo-frame)                                     │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │  "Acme Corp"  []  ← Click anywhere to edit                  ││
│  └─────────────────────────────────────────────────────────────┘│
│                              │                                  │
│                         click                                   │
│                              ▼                                  │
│  ┌─────────────────────────────────────────────────────────────┐│
│  │  Acme Corp [✓] [✗]  ← Seamless input with underline         ││
│  └─────────────────────────────────────────────────────────────┘│
│                              │                                  │
│     Enter / click outside    │    Escape / click ✗              │
│              ▼               ▼                                  │
│           Save            Cancel                                │
└─────────────────────────────────────────────────────────────────┘
```

## Features

- **Click anywhere** on the value to edit
- **Auto-grow** input expands as you type
- **Click outside** or **Enter** to save
- **Escape** or **✗** to cancel
- **Subtle underline** indicates edit mode
- **Preloads on hover** for instant response
- **Smart flash messages** — only shown when the value actually changes

## Keyboard Shortcuts

| Input Type | Submit | Cancel |
|------------|--------|--------|
| Text/Number | Enter | Escape |
| Textarea | Cmd/Ctrl+Enter | Escape |

## API Reference

### `InlineEditFlow::handleField()`

The single method for inline editing:

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

**Behavior:**
- Flash message only appears when the value actually changes (uses Doctrine change detection)
- Exceptions from `onSave` are caught and displayed as form errors
- Entity-level validation (`#[Assert\...]` on properties) applies automatically

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

## Domain Design

Prefer dedicated public methods over generic `update()` methods for inline edits:

```php
// Avoid - passing unrelated fields just to change one
onSave: fn($value) => $product->update(
    (string) $value,           // name (the one we're changing)
    $product->getDescription(), // unchanged
    $product->getPrice(),       // unchanged
    $product->getCategory(),    // unchanged
),

// Prefer - dedicated method with clear intent
onSave: fn($value) => $product->rename((string) $value),
```

**Rule of thumb:** If inline editing requires passing 3+ unrelated values to satisfy an `update()` method, add a dedicated public method (`rename()`, `updatePrice()`, `assignCategory()`, etc.).

This keeps the `onSave` callback clean and makes the domain intent explicit.

## Success Toast

On save, a success toast ("Updated successfully") is delivered via a second Turbo Stream in the success response (`inline_edit_success.stream.html.twig`). It appends a `Toast` component to `#flash-container` (defined in `base.html.twig`). The toast auto-closes after 3500ms (success) or 6000ms (warning/danger) via the `closeable` Stimulus controller (`closeable_controller.js`, uses `stimulus-use` transitions). Duration is type-dependent, set by `Toast::getDuration()`. The toast only appears when the value actually changed (Doctrine change detection).

To disable the toast, pass `successMessage: null` to `InlineEditContext::create()`.

## Supported Entities

| Entity | Route | Field | Controller |
|--------|-------|-------|------------|
| Manufacturer | `app_catalog_manufacturer_inline_name` | name | `ManufacturerController::inlineName` |
| Category | `app_catalog_category_inline_name` | name | `CategoryController::inlineName` |
| Subcategory | `app_catalog_subcategory_inline_name` | name | `SubcategoryController::inlineName` |
| Product | `app_catalog_product_inline_name` | name | `ProductController::inlineName` |
| Supplier | `app_purchasing_supplier_inline_name` | name | `SupplierController::inlineName` |
| VatRate | `app_pricing_vat_rate_inline_name` | name | `VatRateController::inlineName` |
| Customer | `app_customer_inline_fullname` | fullName | `CustomerController::inlineFullName` |

> **Note:** Customer uses `entityVarName: 'customer'` explicitly in `InlineEditContext::create()` because the entity class is `User`, not `Customer`.

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
