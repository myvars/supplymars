# Stimulus Controllers

Stimulus provides progressive enhancement for server-rendered HTML. Controllers add behavior without taking over rendering.

## Philosophy

```
HTML is the source of truth.
JavaScript reads from HTML, writes to HTML.
No client-side state management.
```

## Architecture

```
assets/
├── app.js                    # Entry point, imports bootstrap
├── stimulus_bootstrap.js     # Auto-loads controllers
├── lib/                      # Shared utility modules
│   ├── chart_format.js       # Value formatting for charts (currency, %, integer)
│   └── turbo.js              # Turbo helper functions (turboRefresh)
└── controllers/              # Controller files
    ├── basic_modal_controller.js
    ├── searchbox_controller.js
    └── ...
```

Controllers are auto-discovered by filename convention:
- `example_controller.js` → `data-controller="example"`
- `basic_modal_controller.js` → `data-controller="basic-modal"`

## Controller Anatomy

```javascript
import { Controller } from '@hotwired/stimulus';

export default class extends Controller {
    // Elements to reference
    static targets = ['output', 'input'];

    // Data from HTML attributes
    static values = {
        url: String,
        count: { type: Number, default: 0 },
        enabled: Boolean,
    };

    // Lifecycle: controller connected to DOM
    connect() {
        console.log('Controller connected');
    }

    // Lifecycle: controller disconnected from DOM
    disconnect() {
        console.log('Controller disconnected');
    }

    // Action method (called from data-action)
    submit(event) {
        event.preventDefault();
        this.outputTarget.textContent = this.inputTarget.value;
    }

    // Value changed callback
    countValueChanged(value, previousValue) {
        console.log(`Count changed from ${previousValue} to ${value}`);
    }
}
```

## HTML Binding

### Controller Connection

```html
<div data-controller="example">
    <!-- Controller scope -->
</div>

<!-- Multiple controllers -->
<div data-controller="example other">
```

### Targets

```html
<div data-controller="example">
    <input data-example-target="input">
    <span data-example-target="output"></span>

    <!-- Multiple targets -->
    <li data-example-target="item"></li>
    <li data-example-target="item"></li>
</div>
```

```javascript
// Access in controller
this.inputTarget        // First matching element
this.outputTarget       // First matching element
this.itemTargets        // Array of all matching elements
this.hasInputTarget     // Boolean: does target exist?
```

### Values

```html
<div data-controller="example"
     data-example-url-value="/api/search"
     data-example-count-value="5"
     data-example-enabled-value="true">
```

```javascript
// Access in controller
this.urlValue           // "/api/search" (String)
this.countValue         // 5 (Number)
this.enabledValue       // true (Boolean)

// Set values (updates HTML attribute too)
this.countValue = 10;
```

### Actions

```html
<div data-controller="example">
    <!-- Click event -->
    <button data-action="click->example#submit">Submit</button>

    <!-- Other events -->
    <input data-action="input->example#search">
    <form data-action="submit->example#handleSubmit">
    <select data-action="change->example#update">

    <!-- Multiple actions -->
    <input data-action="focus->example#highlight blur->example#unhighlight">

    <!-- Window/document events -->
    <div data-action="resize@window->example#layout">
    <div data-action="turbo:load@document->example#refresh">
</div>
```

## Existing Controllers

### Modal & Dialog

**`basic_modal_controller`**
- Manages native `<dialog>` element
- Auto-opens when Turbo frame loads content
- Auto-closes on successful form submission
- Handles click-outside and escape key

```html
<dialog data-controller="basic-modal"
        data-action="turbo:frame-load->basic-modal#frameLoaded
                     turbo:submit-end->basic-modal#submitEnd">
    <turbo-frame data-basic-modal-target="frame"></turbo-frame>
</dialog>
```

### Search & Filtering

**`searchbox_controller`**
- Debounced search input (250ms)
- Updates Turbo frame with results
- Manages URL state

```html
<form data-controller="searchbox"
      data-searchbox-base-path-value="/products">
    <input data-searchbox-target="queryInput"
           data-action="input->searchbox#debouncedQueryInputChanged">
</form>
```

**`autosubmit_controller`**
- Auto-submits form on any input change
- Debounced (300ms)

```html
<form data-controller="autosubmit">
    <select data-action="change->autosubmit#submit">
</form>
```

### Form Enhancement

**`dependent_field_controller`**
- Cascading select fields
- Fetches options based on parent selection

```html
<select data-controller="dependent-field"
        data-dependent-field-dependent-value="subcategory"
        data-dependent-field-url-value="/api/subcategories/%id%"
        data-action="change->dependent-field#update">
```

**`submit_form_controller`**
- Programmatic form submission
- Used with dependent fields

### UI Interaction

**`closeable_controller`**
- Auto-closing elements (toasts)
- Optional timer bar animation

```html
<div data-controller="closeable"
     data-closeable-auto-close-value="5000">
    <div data-closeable-target="timerbar"></div>
</div>
```

**`toggle_controller`**
- Show/hide elements
- Optional click-outside-to-close behavior (replaces `user_menu_controller`)

```html
<!-- Simple toggle -->
<div data-controller="toggle">
    <button data-action="click->toggle#toggle">Toggle</button>
    <div data-toggle-target="toggleable" class="hidden">Content</div>
</div>

<!-- Dropdown with click-outside-to-close -->
<div data-controller="toggle" data-toggle-close-on-click-outside-value="true">
    <button data-action="click->toggle#toggle">Menu</button>
    <div data-toggle-target="toggleable" class="hidden">Dropdown</div>
</div>
```

**`card_link_controller`**
- Makes an entire card clickable as a navigation link
- Inner links and buttons still work normally (clicks on `<a>` or `<button>` are ignored)
- Uses Turbo `visit()` for navigation

```html
<div data-controller="card-link"
     data-card-link-url-value="/purchase/order/123"
     data-action="click->card-link#visit"
     class="cursor-pointer">
    <p>Clicking here navigates to the URL</p>
    <a href="/status">This link still works independently</a>
</div>
```

**`sidebar_active_controller`**
- Highlights active nav item in sidebar based on current URL
- Matches links by longest pathname prefix, with query param awareness
- Updates on `connect()` and `turbo:frame-load@window`
- Manages section dropdown open/close and active styling for top-level links, section buttons, child links, icons

```html
<nav data-controller="sidebar-active"
     data-action="turbo:frame-load@window->sidebar-active#update">
    <a data-nav="top" href="/orders">...</a>
    <button data-nav="section">...</button>
    <ul data-nav="dropdown">
        <a data-nav="child" href="/catalog/products">...</a>
    </ul>
</nav>
```

**`theme_toggle_controller`**
- Dark mode toggle
- Persists to localStorage

### Data Visualization

**`bar_chart_controller`** / **`line_chart_controller`** / **`doughnut_chart_controller`**
- Chart.js integration (bar, line, doughnut)
- Shared formatting via `assets/lib/chart_format.js` (currency, percentage, integer)
- Click-to-navigate support (bar, doughnut)

```html
<canvas data-controller="bar-chart"
        data-bar-chart-link-url-value="/reports/product/{label}">
```

### Utilities

**`auto_url_updater_controller`**
- Updates browser URL without navigation
- For filter/search state

**`csrf_protection_controller`** *(not a Stimulus controller — Symfony CSRF side-effect module)*
- Manages CSRF tokens via global event listeners

**`aos_controller`**
- Animate On Scroll initialization

**`sortable_controller`**
- Drag-and-drop reordering (SortableJS)

**`dropzone_controller`**
- File upload with drag-and-drop

**`datepicker_controller`**
- Flowbite datepicker integration

## Patterns

### Debouncing

```javascript
import debounce from 'debounce';

export default class extends Controller {
    initialize() {
        this.search = debounce(this.search.bind(this), 250);
    }

    search() {
        // Called max once per 250ms
    }
}
```

### Turbo Integration

```javascript
// Listen for Turbo events
connect() {
    document.addEventListener('turbo:load', this.handleLoad);
}

disconnect() {
    document.removeEventListener('turbo:load', this.handleLoad);
}

// Or via data-action
// data-action="turbo:load@document->controller#method"
```

### Third-Party Libraries

```javascript
// Lazy load for performance
async connect() {
    const { Chart } = await import('chart.js');
    this.chart = new Chart(this.element, config);
}

disconnect() {
    this.chart?.destroy();
}
```

### Loading States

```javascript
async submit() {
    this.element.ariaBusy = 'true';
    this.submitTarget.disabled = true;

    try {
        await fetch(this.urlValue);
    } finally {
        this.element.ariaBusy = 'false';
        this.submitTarget.disabled = false;
    }
}
```

## Best Practices

1. **Keep controllers small** - One concern per controller

2. **Use targets, not querySelector**:
   ```javascript
   // Good
   this.outputTarget

   // Avoid
   this.element.querySelector('.output')
   ```

3. **Use values for configuration**:
   ```html
   <!-- Good: configurable via HTML -->
   <div data-example-delay-value="500">
   ```

4. **Clean up in disconnect()**:
   ```javascript
   disconnect() {
       this.observer?.disconnect();
       this.chart?.destroy();
   }
   ```

5. **Don't store state in JavaScript** - Use HTML attributes or values

6. **Leverage Turbo events** for navigation-aware behavior

7. **Use `stimulus-use`** for common patterns (transitions, click-outside)
