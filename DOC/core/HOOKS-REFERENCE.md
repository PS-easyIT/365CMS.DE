# 365CMS – Projektdokumentation | Abschnitt: Hooks reference

> **Stand:** 2026-09-13 | **Version:** 3.4.00 | **Status:** Stable | **Update:** 2026-09-13

## English

# Hook reference

The active hook system is implemented in `CMS/core/Hooks.php`. It is a small WordPress-like action/filter registry and is the current extension API used by the core runtime.

## Supported methods

| Method | Purpose |
| --- | --- |
| `Hooks::addAction(string $tag, callable $callback, int $priority = 10)` | register an action callback |
| `Hooks::doAction(string $tag, ...$args)` | trigger an action and call all matching callbacks |
| `Hooks::hasAction(string $tag, ?callable $callback = null, ?int $priority = null)` | test whether an action exists |
| `Hooks::addFilter(string $tag, callable $callback, int $priority = 10)` | register a filter callback |
| `Hooks::applyFilters(string $tag, $value, ...$args)` | apply a filter chain |
| `Hooks::removeAction(string $tag, callable $callback, int $priority = 10)` | remove a registered action |
| `Hooks::removeFilter(string $tag, callable $callback, int $priority = 10)` | remove a registered filter |

## Behavior

- Actions are grouped by tag and priority.
- Filters are grouped by tag and priority.
- All callbacks for a tag are executed in ascending priority order.
- Filter values pass through the callback chain; the return value of each callback becomes the next value.
- The implementation stores callbacks in static arrays.

## Example

```php
use CMS\Hooks;

Hooks::addAction('cms.bootstrap.ready', function () {
    echo 'bootstrap ready';
}, 10);

Hooks::addFilter('cms.render.title', function (string $title) {
    return strtoupper($title);
}, 20);

Hooks::doAction('cms.bootstrap.ready');
$title = Hooks::applyFilters('cms.render.title', '365CMS');
```

## Deutsch

# Hook-Referenz

Das aktive Hook-System ist in `CMS/core/Hooks.php` implementiert. Es ist ein kleines WordPress-ähnliches Action-/Filter-Registry und die aktuelle Erweiterungs-API der Core-Laufzeit.

## Unterstützte Methoden

| Methode | Zweck |
| --- | --- |
| `Hooks::addAction(string $tag, callable $callback, int $priority = 10)` | registriert einen Action-Callback |
| `Hooks::doAction(string $tag, ...$args)` | löst eine Action aus und ruft alle passenden Callbacks auf |
| `Hooks::hasAction(string $tag, ?callable $callback = null, ?int $priority = null)` | prüft, ob eine Action existiert |
| `Hooks::addFilter(string $tag, callable $callback, int $priority = 10)` | registriert einen Filter-Callback |
| `Hooks::applyFilters(string $tag, $value, ...$args)` | wendet eine Filterkette an |
| `Hooks::removeAction(string $tag, callable $callback, int $priority = 10)` | entfernt einen registrierten Action-Callback |
| `Hooks::removeFilter(string $tag, callable $callback, int $priority = 10)` | entfernt einen registrierten Filter-Callback |

## Verhalten

- Actions werden nach Tag und Priorität gruppiert.
- Filter werden nach Tag und Priorität gruppiert.
- Alle Callbacks für einen Tag werden in aufsteigender Priorität ausgeführt.
- Filterwerte werden durch die Callback-Kette geleitet; der Rückgabewert eines Callbacks wird zum nächsten Wert.
- Die Implementierung speichert Callbacks in statischen Arrays.

## Beispiel

```php
use CMS\Hooks;

Hooks::addAction('cms.bootstrap.ready', function () {
    echo 'bootstrap ready';
}, 10);

Hooks::addFilter('cms.render.title', function (string $title) {
    return strtoupper($title);
}, 20);

Hooks::doAction('cms.bootstrap.ready');
$title = Hooks::applyFilters('cms.render.title', '365CMS');
```
