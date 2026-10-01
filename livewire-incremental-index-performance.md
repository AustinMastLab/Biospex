# Incremental Index Rendering Performance

## Problem

The project, expedition, and event index components append each fetched page to
a public Livewire collection. On the next `wire:intersect` request, Livewire
renders the complete parent component to generate its DOM diff. Existing keyed
nodes are normally preserved in the browser, but their server-side Blade
partials still execute.

The affected traits are:

- `app/Traits/WithIncrementalIndex.php`
- `app/Traits/WithIncrementalExpeditions.php`

Both traits retain every previously loaded record in a public collection and
append the next nine records with `concat()`.

## Confirmed affected pages

The following are every current Blade mount point for Livewire components using
one of the two incremental-index traits.

| Surface | URL or route pattern | Components mounted |
| --- | --- | --- |
| Public project index | `/projects` | `front.projects-index` |
| Public expedition index | `/expeditions` | `front.expeditions-index` |
| Public event index | `/events` | `front.events-index`; active and completed are states of this one component |
| Public project detail | `/projects/{slug}` | `front.expeditions-index` and `front.events-index`, filtered to the displayed project |
| Admin project index | `/admin/projects` | `admin.projects-index` |
| Admin expedition index | `/admin/expeditions` | `admin.expeditions-index` |
| Admin event index | `/admin/events` | `admin.events-index` |
| Admin project detail | `/admin/projects/{project}` | `admin.expeditions-index` twice: one active list and one completed list |

No other current Livewire component uses `WithIncrementalIndex` or
`WithIncrementalExpeditions`, and no other Blade view mounts one of these six
components.

## Why projects and expeditions are especially slow

### S3 work during card rendering

Every already-loaded card is rendered again for each incremental request:

- Project cards call `ProjectPresenter::showLogo()`, which performs one
  `Storage::disk('s3')->exists()` call when a `logo_path` exists.
- Expedition cards call `ExpeditionPresenter::showMediumLogo()`, which can
  make two S3 `exists()` calls: one for the medium derivative and one fallback
  to the original.

On the local environment, one project-logo S3 existence check took about
284 ms; nine checks took about 1 second. As more cards have been loaded, the
same S3 checks repeat for every prior card on every scroll request.

### Project rehydration

`front.projects-index` and `admin.projects-index` also implement
`hydrateRecords()`. On each Livewire request, that method refreshes every
already-loaded project through `ProjectService`, including its group and
aggregate counts. The project SQL was not the primary measured bottleneck, but
this work grows with the number of previously displayed cards.

### Event pages

Both event pages have the same full-parent-render behavior. Their event cards
do not call S3 existence checks, so they are less affected. The public event
page also caches each fetched SQL page for one minute, but that does not remove
the repeated server-side rendering of already-loaded cards.

## Recommended implementation

Use **keyed, page-sized Livewire child components or Livewire 4 islands** for
all six index component types.

1. The parent component owns filters, sorting, the next page, and whether more
   pages exist.
2. Each fetched page becomes an immutable keyed child/island that owns only its
   nine cards.
3. `loadMore` appends one new child/island; previously mounted pages remain
   independent and are not rendered again.
4. Sorting, type changes, project filtering, or other list-state changes reset
   the parent’s page list and mount a new first page.
5. Keep `wire:intersect.once` and loading feedback on the parent sentinel.

This preserves Livewire authorization, current sorting/filter behavior, and
incremental scrolling while limiting an ordinary scroll request to rendering
only the new page.

Separately, remove synchronous S3 `exists()` calls from card rendering:

- When `logo_path` is present, generate its URL directly.
- Use the placeholder only when `logo_path` is empty.
- If missing-file fallback is required, establish that state when uploads or
  deletions occur, or cache the existence result outside the render path.

The page-isolation change prevents repeated work; eliminating render-time S3
calls reduces the cost of the new cards themselves.
