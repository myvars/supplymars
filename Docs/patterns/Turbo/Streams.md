# Turbo Streams

Turbo Streams allow the server to push DOM mutations to the browser. SupplyMars uses streams for post-form-submission navigation and flash messages.

## Stream Actions

Turbo provides several actions:

| Action | Effect |
|--------|--------|
| `append` | Add content to end of target |
| `prepend` | Add content to start of target |
| `replace` | Replace entire target element |
| `update` | Replace target's innerHTML |
| `remove` | Remove target element |
| `before` | Insert before target |
| `after` | Insert after target |
| `refresh` | Refresh the page (Turbo 8) |
| `redirect` | Navigate to URL (custom) |

SupplyMars primarily uses `refresh`, `redirect`, `append`, and `replace`.

## Stream Response Format

Streams are returned with MIME type `text/vnd.turbo-stream.html`:

```html
<turbo-stream action="refresh"></turbo-stream>

<turbo-stream action="append" target="flash-container">
    <template>
        <div class="flash flash-success">Order saved</div>
    </template>
</turbo-stream>
```

## TurboAwareRedirector

The `TurboAwareRedirector` detects Turbo requests and returns streams instead of HTTP redirects.

**Location:** `src/Shared/UI/Http/FormFlow/Redirect/TurboAwareRedirector.php`

### Detection Logic

```php
public function to(Request $request, string $url, ...): Response
{
    $hasTurboFrameHeader = $request->headers->has('turbo-frame')
                        || $request->headers->has('Turbo-Frame');

    if ($hasTurboFrameHeader) {
        // Return Turbo Stream response
        return $this->streamResponse($url, $refresh, $forceNavigate);
    }

    // Fallback to HTTP redirect
    return new RedirectResponse($url, $status);
}
```

### Redirect Modes

**1. Refresh in Place**

```html
<turbo-stream action="refresh" id="page-refresh"></turbo-stream>
```

Used when staying on the same page after form submission (e.g., edit form).

**2. Navigate Away**

```html
<turbo-stream action="redirect" url="/orders/abc123"></turbo-stream>
```

Used when redirecting to a different page (e.g., after create, redirecting to show).

**3. Smart Detection**

The redirector compares the referer path with the target path:

```php
private function shouldNavigateAway(Request $request, string $url): bool
{
    $referer = $request->headers->get('referer');
    $refererPath = parse_url($referer, PHP_URL_PATH);
    $targetPath = parse_url($url, PHP_URL_PATH);

    return $refererPath !== $targetPath;
}
```

- Same path → refresh
- Different path → redirect

## Stream Generation

### TurboAwareRedirector

Streams are generated inline in `TurboAwareRedirector::buildStream()`:

```php
private function buildStream(?string $navigateUrl): string
{
    if ($navigateUrl !== null) {
        return sprintf(
            '<turbo-stream action="redirect" url="%s"></turbo-stream>',
            htmlspecialchars($navigateUrl, ENT_QUOTES)
        );
    }

    return '<turbo-stream action="refresh"></turbo-stream>';
}
```

## Stream Templates

### _frame_success_stream.html.twig

Appends flash messages after form submission:

```twig
{% if app.request.headers.get('turbo-frame') == frame %}
    <turbo-stream action="append" target="flash-container">
        <template>
            {{ include('_flashes.html.twig') }}
        </template>
    </turbo-stream>

    {% for stream in app.flashes('stream') %}
        {{ stream|raw }}
    {% endfor %}
{% endif %}
```

**Key points:**
- Only appends if request frame matches expected frame
- Flash messages use Symfony's flash bag
- Additional custom streams can be added via `stream` flash type

## Flash Message Handling

### Flash Container

Located in `base.html.twig`:

```html
<div id="flash-container" class="fixed top-5 right-5 z-50">
    {{ include('_flashes.html.twig') }}
</div>
```

### Flash Types

| Type | Color | Use Case |
|------|-------|----------|
| `success` | Green | Operation completed |
| `error` | Red | Operation failed |
| `warning` | Yellow | Partial success or caution |
| `info` | Blue | Informational message |

### Toast Component

Flashes render as Toast components with auto-dismiss:

```twig
{# _flashes.html.twig #}
{% for type, messages in app.flashes %}
    {% for message in messages %}
        <twig:Toast :type="type">{{ message }}</twig:Toast>
    {% endfor %}
{% endfor %}
```

## Search Result Streams

The Search component uses streams to update result counts and filter state:

```twig
{# templates/components/Search.html.twig #}

{# Update result count #}
<turbo-stream action="replace" target="{{ flowModel|slug }}-result-count">
    <template>
        <span id="{{ flowModel|slug }}-result-count">
            {{ results.totalItemCount }} results
        </span>
    </template>
</turbo-stream>

{# Update filter button state #}
<turbo-stream action="replace" target="{{ flowModel|slug }}-search-filter">
    <template>
        <a id="{{ flowModel|slug }}-search-filter"
           href="{{ path(flowRoute ~ '_search_filter') }}"
           class="{{ hasFilters ? 'bg-blue-100' : '' }}">
            Filters
        </a>
    </template>
</turbo-stream>
```

This allows the filter button to show active state without a full page reload.

## Custom Streams

### Adding Custom Streams

Handlers can add custom streams via the `stream` flash type:

```php
$this->flashMessenger->addFlash('stream',
    '<turbo-stream action="remove" target="item-123"></turbo-stream>'
);
```

These are rendered in `_frame_success_stream.html.twig`:

```twig
{% for stream in app.flashes('stream') %}
    {{ stream|raw }}
{% endfor %}
```

### Use Cases

- Remove deleted item from list without full refresh
- Update a counter elsewhere on page
- Trigger animations on specific elements

## Integration with FormFlow

FormFlow classes use `TurboAwareRedirector` automatically:

```php
// FormFlow.php
return $this->redirector->to(
    request: $request,
    url: $successUrl,
    status: Response::HTTP_SEE_OTHER,
    refresh: $context->getRedirectRefresh(),
    forceNavigate: $forceNavigate,
    frame: $context->getFrame(),
);
```

**FlowContext options:**
- `redirectRefresh: true` - Always use refresh mode
- `redirectRefresh: false` - Use smart detection
- Handler `RedirectTarget` - Override with forced URL

## Best Practices

1. **Use streams for visual feedback**, not critical functionality
   - Streams may fail silently
   - Always have fallback (page refresh works)

2. **Keep stream content minimal**
   - Only send what changed
   - Large streams negate performance benefits

3. **Test without JavaScript**
   - Forms should work with full page reloads
   - Streams are progressive enhancement

4. **Use correct MIME type**
   ```php
   return new Response($content, 200, [
       'Content-Type' => 'text/vnd.turbo-stream.html',
   ]);
   ```

5. **Match target IDs exactly**
   - `target="flash-container"` requires `id="flash-container"` in DOM
   - IDs are case-sensitive

## Debugging

### Network Tab

Stream responses appear as HTML documents with multiple `<turbo-stream>` elements.

### Console Logging

Turbo logs stream processing:

```javascript
// Enable in development
Turbo.setConfirmMethod(() => true);
```

### Common Issues

| Issue | Cause | Fix |
|-------|-------|-----|
| Stream ignored | Wrong MIME type | Set `text/vnd.turbo-stream.html` |
| Target not found | ID mismatch | Check exact ID in DOM |
| Flash not showing | Frame mismatch | Check frame header vs template |
| Double flash | Multiple stream includes | Ensure single include path |
