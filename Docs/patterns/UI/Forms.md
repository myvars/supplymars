# Symfony forms

How forms render, the field kinds the theme supports, and the form helpers this app adds. Read when adding a form or a field, or when a form renders or submits oddly.

Form types, form models and mappers live in `src/{Context}/UI/Http/Form/` (`Type/`, `Model/`, `Mapper/`); `ProductType`, `ProductForm` and `ProductController` in Catalog are a full example. How a controller runs a form is in [FormFlow](../FormFlow/README.md). Where validation rules go (the entity is the source of truth; rules the user can break are repeated on the form model) is in [Security: Validation](../../08-security.md#validation).

## Rules

- Every form renders through the global theme `templates/form/shadcn_theme.html.twig` (registered in `config/packages/twig.yaml` with `form/turnstile.html.twig`). It renders each field with the kit components in `templates/components/` (`Field`, `Input`, `Textarea`, `NativeSelect`, `Checkbox`, `RadioGroup:Item`, `Switch`, `InputGroup`, `DatePicker`, `Calendar`, `Button`).
- Change a field kind in the theme, not per template. The theme is a project file: edit the field's block there, or the component template to change how a control looks.
- A relation on a form model is usually an `?int` id, not an entity (`ProductForm::$categoryId`). The `EntityType` field converts it with `IdToEntityTransformer` (below).
- A dependent field uses `symfonycasts/dynamic-forms` (`addDependent()`), and the field it hangs off submits the form on change: see [Submit on change](StimulusControllers.md#submit-on-change).

## How to

### Opt a form out of the theme

A form that must not look like standard controls (the inline edit form, which blends into the surrounding text) opts out for that form:

```twig
{% form_theme form with ['form_div_layout.html.twig'] only %}
```

### Money and percent

`MoneyType` and `PercentType` show their symbol inside the input's frame as an `InputGroup` addon, before or after the value, wherever the currency's pattern or the percent sign puts it. Nothing to configure.

### Map an entity field to an id

`App\Shared\UI\Form\DataTransformer\IdToEntityTransformer` turns the form model's int id into the entity for the `EntityType` field, and the chosen entity back into its `getId()`. An id that no longer exists throws `TransformationFailedException`. Get one per entity class from `IdToEntityTransformerFactory::for()` (optionally with a finder closure) and add it as a model transformer:

```php
->add('category', EntityType::class, [
    'class' => Category::class,
    'choice_label' => 'name',
    'property_path' => 'categoryId',
])
// …
$builder->get('category')->addModelTransformer($this->transformer->for(Category::class));
```

About a dozen form types do this, and the reusable field types (`SubcategoryIdType`, `Supplier*IdType`) build it in. The per-context `DataTransformer/` folders hold the string-to-enum transformers for filter forms.

### Pick a date from a calendar

Use `DatePickerType` (`src/Shared/UI/Http/Form/Type/DatePickerType.php`) in place of `DateType`. It renders the kit's `DatePicker` and submits a `Y-m-d` string through the calendar's hidden input.

```php
->add('startDate', DatePickerType::class, [
    'required' => false,
    'side' => 'top',
])
```

`side` (`bottom` by default, or `top`) is the edge of the field the calendar opens on. The calendar is positioned inside the form, so in the Turbo modal a field near the bottom needs `top`; opening downwards runs into the modal's scroll area and is cut off, most visibly on a phone. The order and purchase order filters use `top`.

### Test a date picker field

The value is in a hidden input, so a browser test cannot `fillField()` it. Submit the form through the client with the value the calendar would set, as `tests/Order/UI/OrderFilterFlowTest.php` does:

```php
->use(static function (AbstractBrowser $client) use ($values): void {
    $client->submitForm('Apply Filter', $values); // 'order_filter[startDate]' => '2025-01-01', …
})
```

## Known gaps

The field kinds the theme covers, the ones it does not yet (range, colour, collections) and the field options it does not honour are listed in the header comment of `templates/form/shadcn_theme.html.twig`. Add support in the theme when one is needed.

## Gotchas

- **Another registered theme's label blocks win.** If any other theme in `form_themes` defines `checkbox_label` or `radio_label`, it overrides this theme's rows. The theme defines both for that reason; keep them when editing it.
- **An empty form that returns a 500 is a missing constraint, not a theme problem.** `FlowForm` submits with `novalidate`, so a required field left blank reaches the server. If the form model has no constraint on it, the mapper passes `null` to the command and it fails with a `TypeError`. Add `NotBlank` to the form model; the theme then shows the error under the field.

## Related

- [UI patterns](README.md)
- [FormFlow](../FormFlow/README.md): controllers, form models and mappers in use
- [Stimulus controllers](StimulusControllers.md#submit-on-change): submitting a form when a field changes
- [ADR 012](../../adr/012-shadcn-ui-kit.md): the kit and the theme decision
