# Server-Driven UI Pattern

This document describes the server-driven UI architecture used in SupplyMars. Instead of a JavaScript frontend framework, we use Symfony's server rendering with progressive enhancement.

## Philosophy

**Server renders complete HTML. JavaScript enhances interaction.**

```
┌──────────────────────────────────────────────────────────────┐
│  Without JavaScript:                                         │
│  • Pages render completely                                   │
│  • Forms submit and validate                                 │
│  • Navigation works (full page reload)                       │
│  • Every feature is functional                               │
├──────────────────────────────────────────────────────────────┤
│  With JavaScript:                                            │
│  • Turbo: Instant navigation, no full reload                 │
│  • Stimulus: Modals, dropdowns, toasts, charts               │
│  • Flowbite: Datepickers, popovers                           │
│  • Better UX, same functionality                             │
└──────────────────────────────────────────────────────────────┘
```

## Technology Stack

| Layer | Technology | Purpose |
|-------|------------|---------|
| Templates | Twig | HTML rendering |
| Components | Twig Components | Reusable UI pieces |
| Forms | Symfony Forms | Data input, validation |
| Enhancement | Stimulus.js | Progressive JavaScript |
| Navigation | Turbo | SPA-like page loads |
| Styling | Tailwind CSS | Utility-first CSS |
| Assets | Asset Mapper | Zero-build JS delivery |

## When to Use What

### Use Twig Components for:
- Reusable UI elements (buttons, cards, badges)
- Elements with variants (colors, sizes)
- Complex HTML structures that repeat
- Elements needing PHP logic (status colors, icon selection)

### Use Stimulus Controllers for:
- User interactions (click, hover, input)
- DOM manipulation (show/hide, add/remove classes)
- Third-party library integration (charts, datepickers)
- Async behavior (debounce, fetch)

### Use Symfony Forms for:
- All data input
- Validation (server-side, re-render on error)
- CSRF protection
- Complex field relationships (dependent selects)

### Use Turbo for:
- Page navigation (automatic via Turbo Drive)
- Partial updates (Turbo Frames)
- Server-pushed changes (Turbo Streams)

## Quick Reference

### Creating a Twig Component

```php
// src/Shared/UI/Twig/Components/MyComponent.php
#[AsTwigComponent]
final class MyComponent
{
    public string $variant = 'default';
    public ?string $title = null;
}
```

```twig
{# templates/components/MyComponent.html.twig #}
<div class="{{ this.variant }}">
    <h2>{{ title }}</h2>
    {% block content %}{% endblock %}
</div>
```

```twig
{# Usage #}
<twig:MyComponent variant="primary" title="Hello">
    <p>Content here</p>
</twig:MyComponent>
```

### Creating a Stimulus Controller

```javascript
// assets/controllers/example_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['output'];
    static values = { message: String };

    greet() {
        this.outputTarget.textContent = this.messageValue;
    }
}
```

```html
<!-- Usage -->
<div data-controller="example" data-example-message-value="Hello!">
    <button data-action="click->example#greet">Greet</button>
    <span data-example-target="output"></span>
</div>
```

### Form with Stimulus Enhancement

```php
// Form type with Stimulus data attributes
$builder->add('category', EntityType::class, [
    'attr' => [
        'data-action' => 'change->dependent-field#update',
    ],
]);
```

## File Locations

```
src/Shared/UI/Twig/Components/     # PHP component classes
templates/components/              # Component templates
assets/controllers/                # Stimulus controllers
src/{Context}/UI/Http/Form/Type/   # Symfony form types
src/{Context}/UI/Http/Form/Model/  # Form DTOs
assets/styles/app.css              # Tailwind config
importmap.php                      # JavaScript dependencies
```

## Related Documentation

- [Stimulus Controllers](StimulusControllers.md) - Controller patterns
- [Symfony Forms](Forms.md) - Form architecture
- [ADR 008](../../adr/008-server-driven-ui-architecture.md) - Architecture decision
- [Turbo Patterns](../Turbo/README.md) - Navigation and modals
