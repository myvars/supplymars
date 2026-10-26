# Inline edit

Edit one field of a record in place, without a modal: the field is its own Turbo frame that swaps between the value and a one-field form. Read when making a field inline-editable. The flow is `InlineEditFlow` in `myvars/form-flow` (`vendor/myvars/form-flow/src/InlineEdit/`); no form type, form model or handler of your own is needed.

## Rules

- **`onSave` owns persistence.** The flow saves nothing and runs no handler; the callback applies the value to the entity and flushes.
- **Return `$flusher->flush()`** (`App\Shared\Application\FlusherInterface`, true only when Doctrine had changes). A save that changed nothing then shows no toast. Returning `null` counts as changed.
- **Use `property:` instead of copying the entity's rules.** With `property` set on the context, the value is checked against that entity property's constraints before `onSave`, and violations show next to the field. Do not repeat those rules in `formOptions`. A property the entity does not have throws on the first request.
- **Call a domain method.** Prefer a dedicated method for the field (`Product::rename()`). Add one when the entity's `update()` would need three or more unrelated values passed through unchanged; for a short `update()` (`Manufacturer::update($name, $isActive)`) passing the rest unchanged is fine.
- **`field_type` must be text-like** (`TextType`, `TextareaType`, `NumberType`, …). Only the options under [formOptions](#formoptions) reach the field, so a type that needs its own options (`choices`) cannot be used.

## How to

The Product name, as it is in the app.

1. **A partial** that wraps the value in the component, `templates/catalog/product/_inline_name.html.twig`:

   ```twig
   <twig:InlineEdit
       editUrl="{{ path('app_catalog_product_inline_name', {id: product.publicId}) }}"
       frameId="inline-edit-product-{{ product.publicId }}-name"
       displayClass="text-xl font-semibold text-gray-900 dark:text-white"
   >
       {{ product.name }}
   </twig:InlineEdit>
   ```

2. **A route** accepting GET and POST that calls `handleField()`, `ProductController::inlineName`:

   ```php
   #[Route(path: '/product/{id}/inline/name', name: 'app_catalog_product_inline_name', methods: ['GET', 'POST'])]
   public function inlineName(
       Request $request,
       #[ValueResolver('public_id')] Product $product,
       InlineEditFlow $flow,
       FlusherInterface $flusher,
   ): Response {
       return $flow->handleField(
           request: $request,
           value: $product->getName(),
           onSave: function ($value) use ($product, $flusher): bool {
               $product->rename((string) $value);

               return $flusher->flush();
           },
           context: InlineEditContext::create(
               frameId: 'inline-edit-product-' . $product->getPublicId() . '-name',
               displayTemplate: 'catalog/product/_inline_name.html.twig',
               entity: $product,
               property: 'name',
           ),
       );
   }
   ```

3. **Include the partial** where the value is shown (`templates/catalog/product/_product_card.html.twig`).

The existing inline fields are listed by `symfony console debug:router | grep inline`.

### Keep other copies of the value in step (optional)

The success response replaces only the field's frame. To update the same value elsewhere on the page (a breadcrumb, a heading):

- pass `value` as a Closure (`value: fn (): string => $product->getName()`) — the flow calls it again after `onSave` and sends what was stored, not what was typed;
- mark each copy with the frame id: `<twig:PageBreadcrumb … mirrors="inline-edit-product-{{ result.publicId }}-name"/>`, or `data-inline-edit-mirror="<frameId>"` on any element.

Only text-like values are mirrored (string, int, float, `Stringable`), and the copy shows the stored value as escaped text. With a plain (non-Closure) value nothing extra is sent. No controller in the app uses this yet: every inline controller passes a plain value and no `PageBreadcrumb` sets `mirrors`, so after an inline rename the breadcrumb keeps the old name until the page reloads.

## Reference

### `InlineEditContext::create()`

| Parameter | Default | Purpose |
|-----------|---------|---------|
| `frameId` | required | Frame id; must equal the component's `frameId` |
| `displayTemplate` | required | Partial rendered for display and after a save |
| `entity` | required | The record being edited |
| `cancelUrl` | request path | URL the cancel button loads |
| `entityVarName` | class short name, lower-camel | Variable name of the entity in `displayTemplate` |
| `displayTemplateVars` | `[]` | Extra variables for `displayTemplate` |
| `successMessage` | `'Updated successfully'` | Toast after a save that changed something; `null` for none |
| `property` | `null` | Entity property whose constraints are checked before `onSave` |

### `<twig:InlineEdit>`

| Prop | Default | Purpose |
|------|---------|---------|
| `editUrl` | required | The route; the component appends `?edit=1` to load the form |
| `frameId` | required | Frame id, convention `inline-edit-{entity}-{publicId}-{field}` |
| `showEditIcon` | `true` | Pencil icon revealed on hover and focus |
| `displayClass` | `''` | Classes for the frame, to match the surrounding text |
| `editIcon` | `'bi:pencil-square'` | Icon name |
| `editIconSize` | `'h-4 w-4'` | Icon size classes |

The edit link is fetched on hover by Turbo 8's default link prefetch.

### `formOptions`

| Key | Default | Purpose |
|-----|---------|---------|
| `field_type` | `TextType` | Form type of the one field (text-like only) |
| `constraints` | `[]` | Extra rules for this edit only, checked before the entity rules |
| `field_attr` | `[]` | HTML attributes for the input (`min`, `step`, …) |
| `placeholder` | `''` | Input placeholder |

### Keys (`assets/controllers/inline_edit_controller.js`)

| Field | Save | Cancel |
|-------|------|--------|
| Input | Enter, or click outside | Escape |
| Textarea | Cmd/Ctrl+Enter, or click outside | Escape |

## Gotchas

- **`property:` checks only that property's own constraints** (`validatePropertyValue()`: Default group, the value as submitted). Class-level rules (`UniqueEntity`, `Callback`, rules in other groups) do not run, so an inline edit can save a value the handler path would reject. Check those in `onSave`, or don't offer the field inline. Today the only class-level rule on an inline-edited entity is `User`'s `UniqueEntity(email)`, and the inline field there is `fullName`.
- **Exceptions thrown in `onSave` show as a form-level error, below the buttons**, not next to the field, with the exception message as written. E.g. `Product::rename('   ')` passes `NotBlank` but throws "Product name cannot be empty".
- **The frame id is written twice**, in the partial's `frameId` and in the context's `frameId`. If they differ, the form and the success stream miss the frame.
- **`editUrl` must have no query string.** The component appends `?edit=1` literally.
- **The route needs GET and POST.** GET without `edit` renders the display (cancel), GET with `edit` renders the form, POST saves.
- **The toast and mirrors come from the app's success template.** The package's default `inline_edit_success.stream.html.twig` sends no flash stream. The app's override, `templates/shared/form_flow/inline_edit_success.stream.html.twig`, appends `_flashes.html.twig` (`FlashToast`, timed by `FlashToast::getDuration()`) to the `flash-container` toaster in `base.html.twig`, and keeps the mirror stream; keep both when editing it.
- **The form skips the global form theme.** `templates/shared/form_flow/inline_edit_form.html.twig` uses `form_div_layout.html.twig` only, so the input blends into the text. Changes to `shadcn_theme.html.twig` do not reach it.
- **Errors return 422** and keep the form open in the frame.
- **Customer passes `entityVarName: 'customer'`** (`CustomerController::inlineFullName`): the entity class is `User`, so the derived name would be `user`.

## Related

- [FormFlow](../FormFlow/README.md)
- [Turbo frames, modals and streams](../Turbo/README.md)
- [Security: Validation](../../08-security.md#validation): where validation rules live
- Tests: `tests/Catalog/UI/UpdateProductFlowTest.php` and the other `tests/*/UI/Update*FlowTest.php` for Manufacturer, Category, Subcategory, Supplier, VatRate and Customer
