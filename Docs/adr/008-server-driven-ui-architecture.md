# ADR 008: Server-Driven UI Architecture

## Status

Accepted

## Context

SupplyMars needed an interactive, modern UI for managing orders, products, suppliers, and reporting. The team evaluated several approaches:

| Approach | Pros | Cons |
|----------|------|------|
| **React/Vue SPA** | Rich interactivity, component ecosystem | API duplication, complex state, build tooling, hydration issues |
| **Inertia.js** | Server routing, client rendering | Vendor lock-in, PHP-JS coupling, SSR complexity |
| **Livewire** | PHP-only reactivity | Performance overhead, large payloads, debugging difficulty |
| **Server-rendered + Turbo** | Simple, progressive enhancement | Less "app-like" feel, learning curve for Turbo |

Key considerations:

1. **Team expertise**: Strong PHP/Symfony skills, limited frontend specialization
2. **Maintenance burden**: Minimize JavaScript complexity and build tooling
3. **Form handling**: Heavy form usage benefits from Symfony's validation
4. **SEO/accessibility**: Not critical for admin tool, but good defaults preferred
5. **Time to market**: Faster iteration with server rendering

## Decision

We adopted a **server-driven UI architecture** using:

- **Symfony Forms** for data input with server-side validation
- **Twig Components** for reusable UI elements
- **Stimulus.js** for progressive enhancement
- **Turbo** for SPA-like navigation (see [ADR 007](007-turbo-frame-modal-architecture.md))
- **Asset Mapper** for zero-build JavaScript delivery
- **Tailwind CSS** for utility-first styling

### Core Principle: Server Renders, JavaScript Enhances

```
┌─────────────────────────────────────────────────────────────────┐
│                         SERVER                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │  Symfony    │  │    Twig     │  │      Twig Components    │  │
│  │   Forms     │  │  Templates  │  │  (Button, Card, Modal)  │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
│         │                │                      │               │
│         └────────────────┴──────────────────────┘               │
│                          │                                      │
│                    Complete HTML                                │
└─────────────────────────────────────────────────────────────────┘
                           │
                           ▼
┌─────────────────────────────────────────────────────────────────┐
│                        BROWSER                                  │
│  ┌─────────────┐  ┌─────────────┐  ┌─────────────────────────┐  │
│  │   Turbo     │  │  Stimulus   │  │      Flowbite/          │  │
│  │  (Navigate) │  │ (Enhance)   │  │      Chart.js           │  │
│  └─────────────┘  └─────────────┘  └─────────────────────────┘  │
│                                                                 │
│  JavaScript adds: modals, search, charts, dropdowns, toasts     │
│  But: Forms work without JS, every state is a URL               │
└─────────────────────────────────────────────────────────────────┘
```

### Technology Choices

**Twig Components** (not Symfony UX Live Components):
- Compile-time, not runtime
- No WebSocket/HTTP overhead
- Type-safe props via PHP classes
- Familiar Twig syntax

**Stimulus.js** (not Alpine.js or vanilla JS):
- Structured controller pattern
- Clear data flow (targets, values, actions)
- Official Symfony integration
- Small footprint (~6KB)

**Asset Mapper** (not Webpack/Vite):
- Zero configuration
- Native ES modules
- No build step in development
- Simpler deployment pipeline

**Tailwind CSS** (not Bootstrap or custom CSS):
- Utility-first reduces CSS bloat
- Design system via theme config
- Excellent Twig template integration
- Dark mode via class strategy

## Consequences

### Positive

- **Simpler mental model**: State lives in HTML, not JavaScript
- **Form validation**: Symfony's validator works naturally
- **Fast iteration**: Change Twig, refresh browser
- **No API layer**: Controllers return HTML, not JSON
- **Accessibility by default**: Semantic HTML, proper form labels
- **Cacheable**: GET requests can be cached
- **Debuggable**: View source shows actual content
- **Progressive enhancement**: Core functionality works without JS

### Negative

- **Less "app-like"**: No optimistic updates or offline support
- **Page weight**: Full HTML on each navigation (mitigated by Turbo)
- **Complex interactions**: Some UX patterns harder without rich JS
- **Component limitations**: Twig components less flexible than React
- **Learning curve**: Team must learn Stimulus patterns

### Trade-offs Accepted

| Capability | Our Approach | SPA Approach |
|------------|--------------|--------------|
| Form validation | Server-side, re-render on error | Client-side, instant feedback |
| Loading states | Turbo's [busy] attribute | Custom spinners per component |
| Optimistic UI | Not supported | Update before server confirms |
| Offline support | Not supported | Service workers, local state |
| Real-time updates | Polling or Turbo Streams | WebSocket subscriptions |

These trade-offs are acceptable for an internal operations tool where:
- Users have reliable internet
- Data consistency matters more than perceived speed
- Forms are the primary interaction pattern

## Implementation Notes

### Component Architecture

```
src/Shared/UI/Twig/Components/   # PHP component classes
templates/components/            # Component templates

# Example: Button component
Button.php                       # Props, variant logic
Button.html.twig                 # HTML structure
```

### Stimulus Controller Pattern

```javascript
// assets/controllers/example_controller.js
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    static targets = ['output'];           // Elements to reference
    static values = { url: String };       // Data from HTML

    connect() { }                          // Lifecycle: added to DOM
    disconnect() { }                       // Lifecycle: removed from DOM

    doSomething(event) {                   // Action method
        this.outputTarget.textContent = this.urlValue;
    }
}
```

### Form Flow Integration

Forms use the FormFlow pattern ([ADR 006](006-formflow-controller-pattern.md)):

1. GET: Render form with Twig
2. POST: Validate, execute handler, redirect
3. Error: Re-render form with validation errors (HTTP 422)
4. Success: Turbo Stream redirect/refresh

### Key Files

| Category | Location |
|----------|----------|
| Twig Components | `src/Shared/UI/Twig/Components/`, `templates/components/` |
| Stimulus Controllers | `assets/controllers/` |
| Form Types | `src/{Context}/UI/Http/Form/Type/` |
| Form Models | `src/{Context}/UI/Http/Form/Model/` |
| Asset Config | `importmap.php`, `assets/styles/app.css` |
| Tailwind Config | `assets/styles/app.css` (embedded config) |

## Related Documentation

- [UI Patterns](../patterns/UI/README.md) - Detailed component and controller docs
- [Turbo Architecture](007-turbo-frame-modal-architecture.md) - Navigation and modal system
- [FormFlow Pattern](../patterns/FormFlow/README.md) - Form handling abstraction
