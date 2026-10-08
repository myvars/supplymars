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
| `redirect` | Navigate to URL (custom; defined in `assets/app.js` as `StreamActions.redirect`) |

SupplyMars primarily uses `refresh`, `redirect`, `append`, and `replace`.

## Stream Response Format

Streams are returned with MIME type `text/vnd.turbo-stream.html`:

```html
<turbo-stream action="refresh"></turbo-stream>

<turbo-stream action="append" target="flash-container">
    <template>
        <!-- a FlashToast, rendered by _flashes.html.twig -->
    </template>
</turbo-stream>
```

## TurboAwareRedirector

The `TurboAwareRedirector` detects Turbo requests and returns streams instead of HTTP redirects.

**Location:** `vendor/myvars/form-flow/src/Redirect/TurboAwareRedirector.php`

### Detection Logic

```php
public function to(Request $request, string $url, bool $refresh = false, int $status = 303, bool $forceNavigate = false): Response
{
    $hasTurboFrameHeader = $request->headers->has('turbo-frame') || $request->headers->has('Turbo-Frame');

    if ($hasTurboFrameHeader) {
        $request->setRequestFormat(TurboBundle::STREAM_FORMAT);
        $shouldNavigate = $forceNavigate || ($refresh && $this->shouldNavigateAway($request, $url));

        return new Response(
            $this->buildStream($shouldNavigate ? $url : null),
            Response::HTTP_OK,
            ['Content-Type' => self::TURBO_STREAM_MIME]
        );
    }

    // Fallback to HTTP redirect
    return new RedirectResponse($url, $status);
}
```

### Redirect Modes

**1. Refresh in Place**

```html
<turbo-stream action="refresh"></turbo-stream>
```

The default for a successful flow inside a Turbo Frame (create, update, filter, action).

**2. Navigate Away**

```html
<turbo-stream action="redirect" url="/orders/abc123"></turbo-stream>
```

Used when the handler returns a `RedirectTarget`, or when smart detection (below) finds the target is a different page.

**3. Smart Detection**

When `$refresh` is true, the redirector compares the referer path with the target path:

```php
private function shouldNavigateAway(Request $request, string $url): bool
{
    $referer = $request->headers->get('referer');
    $refererPath = parse_url($referer, PHP_URL_PATH);
    $targetPath = parse_url($url, PHP_URL_PATH);

    return $refererPath !== $targetPath;
}
```

- `forceNavigate` (the handler returned a `RedirectTarget`) → redirect
- `refresh: true` (set by `FlowContext::forDelete()` and `forConfirm()`) → compare paths: same path or no referer → refresh, different path → redirect
- Otherwise → refresh

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

```twig
<twig:Sonner id="flash-container" position="top-right" class="top-4 right-4" :closeButton="true" :expand="true">
    {% if not app.request.headers.has('Turbo-Frame') %}
        {{ include('_flashes.html.twig') }}
    {% endif %}
</twig:Sonner>
```

### Flash Types

| Type | Color | Use Case |
|------|-------|----------|
| `success` | Green | Operation completed |
| `danger` | Red | Operation failed |
| `warning` | Orange | Partial success or caution |
| `info` | Blue | Informational message |

### Toast Component

Flashes render as Toast components with auto-dismiss:

```twig
{# _flashes.html.twig #}
{% for label, messages in app.flashes(['success','info','warning','danger']) %}
    {% for message in messages %}
        <twig:FlashToast type="{{ label }}" :message="message"/>
    {% endfor %}
{% endfor %}
```

## Search Result Streams

The Search component uses streams to update result counts and filter state:

```twig
{# templates/components/Search.html.twig #}

<turbo-stream action="replace" target="{{ flowModel|slug }}-result-count">
    <template>{{ block('result_count') }}</template>
</turbo-stream>
{% if showFilter %}
    <turbo-stream action="replace" target="{{ flowModel|slug }}-search-filter">
        <template>{{ block('filter') }}</template>
    </turbo-stream>
{% endif %}
```

This allows the filter button to show active state without a full page reload.

## Custom Streams

### Adding Custom Streams

Handlers can add custom streams via the `stream` flash type:

```php
$request->getSession()->getFlashBag()->add('stream',
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
// vendor/myvars/form-flow/src/Concerns/RedirectsResponses.php

// successRedirect()
return $this->getRedirector()->to(
    $request,
    $context->resolveSuccessUrl($request, $this->getUrlGenerator()),
    $context->isRedirectRefresh(),
    $context->getRedirectStatus()
);

// redirectToTarget() — the handler returned a RedirectTarget
return $this->getRedirector()->to($request, $url, refresh: false, status: $redirect->status(), forceNavigate: true);
```

**What decides the mode:**
- `FlowContext::forDelete()` and `forConfirm()` — smart detection (navigate if the target path differs from the referer)
- All other factories — refresh in place
- Handler `RedirectTarget` — always navigate to that URL

There is no public option to change this per call.

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

Log each stream as it arrives:

```javascript
document.addEventListener('turbo:before-stream-render', (event) => console.log(event.target));
```

### Common Issues

| Issue | Cause | Fix |
|-------|-------|-----|
| Stream ignored | Wrong MIME type | Set `text/vnd.turbo-stream.html` |
| Target not found | ID mismatch | Check exact ID in DOM |
| Flash not showing | Frame mismatch | Check frame header vs template |
| Double flash | Multiple stream includes | Ensure single include path |
