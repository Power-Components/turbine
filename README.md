# Turbine

The framework-agnostic data engine behind the Turbine table component.

It runs **search, filters, sort, pagination and row transformation** over Eloquent, the Query Builder, Collections or Scout, and returns a plain **JSON envelope**. There is no Blade and no JavaScript in the box — so the same engine can feed an Inertia (React / Vue) page, a SPA talking to a JSON API, or a plain AJAX table.

> **You describe the grid once in PHP. The engine does the work. Your front-end just renders the JSON.**

## Contents

- [Requirements](#requirements)
- [Install](#install)
- [Quickstart](#quickstart-inertia--api--ajax)
- [How it fits together](#how-it-fits-together)
- [The request contract](#the-request-contract-what-your-front-end-sends)
- [The response envelope](#the-response-envelope-what-you-get-back)
- [Actions](#actions)
- [Building blocks](#building-blocks)
- [Reusing an existing component](#reusing-an-existing-component)
- [Lower-level API](#lower-level-api)
- [License](#license)

## Requirements

- PHP 8.3+
- A Laravel 12 / 13 application (the engine uses `illuminate/*` — database, pagination, request, cache — but **not** the full framework)

## Install

```bash
composer require power-components/turbine
```

> Already using the full Turbine component package? It depends on this engine — keep writing normal Turbine components, nothing changes for you.

## Quickstart

Everything a front-end needs is described in PHP with the `Turbine` builder. Point it at a datasource, declare fields, columns and filters, feed it the request, and return the envelope:

```php
use PowerComponents\Turbine\Button;
use PowerComponents\Turbine\Column;
use PowerComponents\Turbine\Components\Rules\RuleActions;
use PowerComponents\Turbine\Fields;
use PowerComponents\Turbine\Turbine;

class UserGridController
{
    public function __invoke(Request $request): Response
    {
        return Inertia::render('users', [
            'columns' => fn () => $this->columns(),
            'grid' => fn () => $this->turbine($request)->toArray(),
        ]);
    }

    private function fields()
    {
        return (new Fields())
            ->add('id')
            ->add('name')
            ->add('email');
    }

    /**
     * @return array<int, Column>
     */
    private function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make('Name', 'name')->searchable()->sortable(),
            Column::make('Email', 'email')->searchable()->sortable(),
        ];
    }

    /**
     * @return array<int, Button>
     */
    private function actions(User $user): array
    {
        return [
            Button::add('view')
                ->slot('View')
                ->route('users.show', ['user' => $user->id]),
        ];
    }

    /**
     * @return array<int, RuleActions>
     */
    private function actionsRules(User $user): array
    {
        return [
            (new RuleActions('view'))->when(fn ($r) => $r->id % 2 === 0)->hide(),
            (new RuleActions('view'))->when(fn ($r) => $r->id === 1)->setAttribute('disabled', 'disabled'),
        ];
    }

    private function turbine(Request $request): Turbine
    {
        return Turbine::make()
            ->datasource(fn () => User::query())
            ->fields($this->fields())
            ->columns(fn () => $this->columns())
            ->filters([])
            ->actions(fn (User $user) => $this->actions($user))
            ->actionRules(fn (User $user) => $this->actionsRules($user))
            ->tableName('users')
            ->primaryKey('id')
            ->perPage(10)
            ->fromRequest($request);
    }
}
```

```php
// routes/web.php (or routes/api.php)
Route::get('/users/grid', UserGridController::class);
```

That endpoint now handles search, filtering, sorting and pagination. The state comes **entirely from the request** — your only job is to hand over the data (`datasource`) and describe the shape (`fields` / `columns` / `filters` / `actions`).

Prefer the raw array (e.g. an Inertia prop)? Use `->toArray()` instead of `->toResponse()`.

### Builder reference

| Method | Purpose |
|---|---|
| `datasource(Closure)` | Returns a fresh Eloquent/Query Builder, Scout Builder or Collection. **Required.** |
| `fields(Fields)` | The row shape; each `add()` maps an output key to a column or a closure. |
| `columns(array)` | `Column` list — drives the `columns` schema and which fields are sortable/searchable. |
| `filters(array)` | `Filter*` list — the filters the front-end may apply. |
| `actions(Closure)` | `fn ($row) => Button[]` — per-row actions, resolved server-side. |
| `actionRules(Closure)` | `fn ($row) => Rule[]` — conditional `hide` / `disable` / `setAttribute` per row. |
| `transformQuery(Closure)` | `fn ($query) => $query` — extra constraints (scopes, tenancy, joins). |
| `relationSearch(array)` | Search across relations, e.g. `['category' => ['name']]`. |
| `primaryKey(string)` | Row identity used to key the `actions` map. Default `id`. |
| `tableName(string)` | Table name (used by summaries cache). |
| `perPage(int)` | Page size. Default `15`. Server-owned — the client cannot override it. |
| `pageName(string)` | Query-string page parameter. Default `page`. |
| `fromRequest(Request)` | Reads state from the `turbine` request key (see below). |
| `state(array)` | Same, but from a raw array (Inertia JSON body, a queue job, tests). |
| `toArray()` / `toResponse()` | Build the envelope / a `JsonResponse`. |

## How it fits together

```
                 ?turbine[...] + ?page
Front-end  ───────────────────────────────▶  Turbine (your PHP definition)
(React/Vue/                                        │
 fetch/axios)                                      ▼
                                                Turbine engine
                                     search · filter · sort · paginate · transform
                                                   │
           JSON envelope { data, meta, columns, filters, actions }
Front-end  ◀───────────────────────────────────────┘
renders table
```

The front-end owns **rendering and interaction**; the core owns **data and rules**. State travels in the request; results travel in the envelope. Nothing framework-specific (no `wire:*`, no Blade) ever crosses the line.

## The request contract (what your front-end sends)

All grid state lives under a single `turbine` key, plus the standard `?page`:

| Query key | Meaning | Example |
|---|---|---|
| `turbine[search]` | Global search term | `turbine[search]=maria` |
| `turbine[sortField]` | Column to sort by | `turbine[sortField]=name` |
| `turbine[sortDirection]` | `asc` or `desc` | `turbine[sortDirection]=desc` |
| `turbine[filters][<key>][<field>]` | A filter value, keyed by the filter's `key` and target `field` | `turbine[filters][input_text][name]=ana` |
| `page` (or your `pageName`) | Current page | `page=2` |

The `<key>` is the filter type reported back in the envelope's `filters[].key`:

| Filter class | `key` |
|---|---|
| `FilterInputText` | `input_text` |
| `FilterSelect` / `FilterEnumSelect` | `select` |
| `FilterMultiSelect` / `FilterMultiSelectAsync` | `multi_select` |
| `FilterBoolean` | `boolean` |
| `FilterNumber` | `number` |
| `FilterDatePicker` | `date` |
| `FilterDateTimePicker` | `datetime` |

Example — search *ana* in `name`, filter `status = active`, sort by `name` desc, page 2:

```
/users/grid?turbine[search]=ana
          &turbine[sortField]=name
          &turbine[sortDirection]=desc
          &turbine[filters][input_text][name]=ana
          &turbine[filters][select][status]=active
          &page=2
```

With Inertia you can POST the same structure as a JSON body and feed it via `->state($request->input('turbine', []))`.

## The response envelope (what you get back)

```jsonc
{
  "data": [
    { "id": 1, "name": "Ana", "email": "ana@acme.test", "created_at_formatted": "01/03/2026" }
  ],
  "meta": {
    "pagination": { "current_page": 2, "per_page": 15, "from": 16, "to": 30, "total": 84, "last_page": 6 },
    "sort":       { "field": "name", "direction": "desc", "multiSort": false, "sortArray": [] },
    "search": "ana",
    "filters": { "input_text": { "name": "ana" } },
    "filterBuilder": {}
  },
  "columns": [
    { "field": "name", "title": "Name", "sortable": true, "searchable": true, "hidden": false }
  ],
  "filters": [
    { "key": "input_text", "field": "name", "column": "name", "title": "" }
  ],
  "actions": {
    "1": [
      { "id": "edit",   "label": "Edit",   "tag": "a",      "visible": true, "disabled": false,
        "confirm": null,             "event": { "type": "link", "href": "/users/1/edit", "target": "_self" } },
      { "id": "delete", "label": "Delete", "tag": "button", "visible": true, "disabled": false,
        "confirm": "Delete this user?", "event": { "type": "dispatch", "event": "deleteUser", "params": { "id": 1 } } }
    ]
  }
}
```

| Key | What it is |
|---|---|
| `data` | The transformed rows — exactly the keys you declared in `fields()`. Internal `__turbine*` keys are stripped. |
| `meta.pagination` | Paginator state. `total` / `last_page` are present for length-aware pagination. |
| `meta.sort` / `meta.search` / `meta.filters` | The state the engine actually applied — echo it back into your UI controls. |
| `columns` | Column schema to build headers: `field`, `title`, `sortable`, `searchable`, `hidden`. |
| `filters` | Filters the user may apply, with the `key` your requests must use. |
| `actions` | Map of **primary key → resolved action descriptors** (see below). Empty when no `actions()` are declared. |

## Actions

Actions are described with the fluent `Button` DSL and **resolved on the server** — visibility and rule outcomes arrive already computed, so there is no rule logic in the front-end.

```php
->actions(fn (User $user) => [
    Button::add('edit')->slot('Edit')->route('users.edit', ['user' => $user->id]),
    Button::add('view')->slot('View')->link("/users/{$user->id}"),
    Button::add('delete')->slot('Delete')
        ->dispatch('deleteUser', ['id' => $user->id])
        ->confirm('Delete this user?')
        ->can(fn ($u) => $u->deletable),
])
```

Each button becomes a descriptor whose `event` tells the front-end **what to do when it is clicked**. The `type` is all you interpret:

| `event.type` | Payload | Suggested front-end handling |
|---|---|---|
| `link` | `href`, `target` | Navigate / `<a>` |
| `dispatch` | `event`, `params` | Emit your own app event / call an endpoint |
| `dispatchTo` | `to`, `event`, `params` | Same, targeted at a component |
| `dispatchSelf` | `event`, `params` | Emit on the current view |
| `modal` | `component`, `params` | Open a modal |
| `toggleDetail` | `rowId` | Expand a row detail |
| `call` / `parent` | `method`, `params` | Invoke a handler |

Other descriptor fields: `id` (the action name), `label`, `icon`, `tag`, `visible`, `disabled`, `confirm` (a plain string, or `null`) and `confirmPrompt` (whether `confirm` expects typed confirmation).

> The DSL is deliberately framework-neutral: buttons carry a neutral `event` descriptor, never a framework-specific binding. (A companion framework package may add `wire:*` bindings for server-side Blade — the headless envelope is unaffected.)

### Conditional rules

Rules allow server-side conditional logic applied per row, action button, or cell element before sending the JSON envelope to the client.

#### 1. Action Rules (`RuleActions`)
Modify, hide, disable, or redirect action buttons dynamically per row:

```php
use PowerComponents\Turbine\Components\Rules\RuleActions;

Turbine::make()
    ->actionRules(fn (User $user) => [
        // Hide delete button for admin users
        (new RuleActions('delete'))
            ->when(fn (User $u) => $u->is_admin)
            ->hide(),

        // Disable edit button for inactive users
        (new RuleActions('edit'))
            ->when(fn (User $u) => ! $u->is_active)
            ->disable(),

        // Override button label / slot
        (new RuleActions('view'))
            ->when(fn (User $u) => $u->is_vip)
            ->slot('VIP Profile'),

        // Set custom HTML or CSS attributes
        (new RuleActions('delete'))
            ->when(fn (User $u) => $u->has_warnings)
            ->setAttribute('class', 'bg-red-500 text-white'),

        // Override action event to redirect
        (new RuleActions('show'))
            ->when(fn (User $u) => $u->is_external)
            ->redirect('https://external-system.test/user/' . $user->id),
    ]);
```

#### 2. Row Rules (`RuleRows`)
Apply styling, attributes, or detail views to entire table rows:

```php
use PowerComponents\Turbine\Components\Rules\RuleRows;

Turbine::make()
    ->actionRules(fn (User $user) => [
        // Highlight rows matching condition
        (new RuleRows())
            ->when(fn (User $u) => $u->is_admin)
            ->setAttribute('class', 'bg-blue-50 font-bold'),

        // Set custom expanded row detail view
        (new RuleRows())
            ->when(fn (User $u) => $u->has_details)
            ->detailView('users.details-panel', ['user' => $user->id]),

        // Helper loop conditions for zebra striping or page positions
        (new RuleRows())->alternating()->setAttribute('class', 'bg-gray-50'),
        (new RuleRows())->firstOnPage()->setAttribute('class', 'border-t-2 border-primary'),
        (new RuleRows())->lastOnPage()->setAttribute('class', 'border-b-2 border-primary'),
    ]);
```

#### 3. Selection & Element Rules
Control selection checkboxes, radio buttons, toggleable cells, or inline edit cells:

```php
use PowerComponents\Turbine\Components\Rules\{RuleCheckbox, RuleRadio, RuleToggleable, RuleEditOnClick};

// Checkbox selection rule
(new RuleCheckbox())
    ->when(fn (User $u) => $u->is_system_user)
    ->disable()
    ->hide()
    ->applyRowClasses('non-selectable');

// Radio selection rule
(new RuleRadio())
    ->when(fn (User $u) => ! $u->is_active)
    ->disable();

// Toggleable column cell rule
(new RuleToggleable('is_active'))
    ->when(fn (User $u) => $u->is_protected)
    ->hide();

// Edit-on-click cell rule
(new RuleEditOnClick('name'))
    ->when(fn (User $u) => $u->is_locked)
    ->disable();
```

## Building blocks

All of these live in the core namespace `PowerComponents\Turbine\`:

- **`Fields`** — the row shape. `->add('field')` passes a column through; `->add('key', fn ($row) => ...)` computes a value.
- **`Column`** — `Column::make('Title', 'field')` (or `Column::make('Title', 'alias', 'real_column')`), chained with `->sortable()`, `->searchable()`, `->hidden()`.
- **`Components\Filters\*`** — `FilterInputText`, `FilterSelect`, `FilterMultiSelect`, `FilterNumber`, `FilterBoolean`, `FilterDatePicker`, `FilterDateTimePicker`, `FilterEnumSelect`.
- **`Button`** — the action DSL described above.

### Search & Relation Search

Global search filters records across all columns configured with `->searchable()`:

```php
Column::make('Name', 'name')->searchable(),
Column::make('Email', 'email')->searchable(),
```

#### Search across relationships
Use `relationSearch()` to allow global search across Eloquent relations:

```php
Turbine::make()
    ->datasource(fn () => User::query())
    ->columns($columns)
    ->relationSearch([
        'category' => ['name', 'slug'],
        'profile'  => ['bio', 'phone'],
    ]);
```

### Filter Options

Turbine includes a complete suite of filter components located in `PowerComponents\Turbine\Components\Filters\*`. Filters can be passed directly as an array or instantiated via `FilterManager`.

#### 1. Input Text (`FilterInputText`)
Filters text fields with configurable search operators (`contains`, `starts_with`, `ends_with`, `exact`):

```php
use PowerComponents\Turbine\Components\Filters\FilterInputText;

new FilterInputText('name', 'users.name')
    ->operators(['contains', 'starts_with', 'exact'])
    ->placeholder('Search by name...')
    ->default('John');
```

#### 2. Select & Enum Select (`FilterSelect`, `FilterEnumSelect`)
Single selection filter backed by a collection, array, or PHP Enum:

```php
use PowerComponents\Turbine\Components\Filters\{FilterSelect, FilterEnumSelect};

// Standard collection or array source
new FilterSelect('status')
    ->dataSource(collect([
        ['id' => 'active', 'name' => 'Active'],
        ['id' => 'pending', 'name' => 'Pending'],
    ]))
    ->optionValue('id')
    ->optionLabel('name')
    ->depends(['country_id']); // Re-evaluates when parent filter changes

// PHP Enum source (auto-detects labelTurbineFilter() or Enum value)
new FilterEnumSelect('status')
    ->dataSource(UserStatusEnum::cases());
```

#### 3. Multi-Select & Async Multi-Select (`FilterMultiSelect`, `FilterMultiSelectAsync`)
Multi-selection filter for array values or remote API endpoints:

```php
use PowerComponents\Turbine\Components\Filters\{FilterMultiSelect, FilterMultiSelectAsync};

// In-memory multi select
new FilterMultiSelect('role_id')
    ->dataSource(Role::all())
    ->optionValue('id')
    ->optionLabel('name');

// Async multi select from remote URL
new FilterMultiSelectAsync('user_id')
    ->url('https://api.example.com/users')
    ->method('POST')
    ->parameters(['active' => true]);
```

#### 4. Boolean (`FilterBoolean`)
Boolean toggle filter (`true` / `false`):

```php
use PowerComponents\Turbine\Components\Filters\FilterBoolean;

new FilterBoolean('is_active')
    ->label('Active', 'Inactive');
```

#### 5. Number & Range (`FilterNumber`)
Numeric range filter with thousands and decimal separators:

```php
use PowerComponents\Turbine\Components\Filters\FilterNumber;

new FilterNumber('price')
    ->thousands('.')
    ->decimal(',')
    ->placeholder('Min price', 'Max price');
```

#### 6. Date & Date-Time Pickers (`FilterDatePicker`, `FilterDateTimePicker`)
Date and timestamp range filters:

```php
use PowerComponents\Turbine\Components\Filters\{FilterDatePicker, FilterDateTimePicker};

new FilterDatePicker('created_at');
new FilterDateTimePicker('updated_at');
```

#### 7. Dynamic Filter (`FilterDynamic`)
Pass custom front-end filter props and component names:

```php
use PowerComponents\Turbine\Components\Filters\FilterDynamic;

new FilterDynamic('custom_field')
    ->component('custom-slider-filter', ['min' => 0, 'max' => 100]);
```

#### 8. Custom Filter Query Callbacks (`builder` and `collection`)
Override standard query filtering logic per filter instance:

```php
new FilterInputText('title')
    ->builder(function ($query, $value) {
        return $query->whereRaw('LOWER(title) LIKE ?', ['%' . strtolower($value) . '%']);
    })
    ->collection(function ($collection, $value) {
        return $collection->filter(fn ($row) => str_contains($row['title'], $value));
    });
```

### Datasources

`datasource()` accepts a closure or value returning any supported data source:

```php
// 1. Eloquent Model, Query Builder, or Relation
Turbine::make()->datasource(fn () => User::query());
Turbine::make()->datasource(fn () => DB::table('users')->where('active', true));
Turbine::make()->datasource(fn () => $user->posts());

// 2. Laravel Scout Builder (search engines)
Turbine::make()->datasource(fn () => User::search($request->get('search')));

// 3. Collection or Array (in-memory data)
Turbine::make()->datasource(fn () => collect([
    ['id' => 1, 'name' => 'Alice'],
    ['id' => 2, 'name' => 'Bob'],
]));
```

The engine automatically inspects the datasource type and delegates query execution, searching, filtering, sorting, and pagination to the matching processor.

### Custom DataSources

You can register custom DataSource processors to handle custom REST APIs, DTO repositories, Elasticsearch, or third-party SDKs by implementing `PowerComponents\Turbine\Contracts\DataSourceProcessor` (or extending `DataSourceBase`).

#### 1. Implementation Example

```php
namespace App\DataSources;

use Illuminate\Pagination\LengthAwarePaginator;
use PowerComponents\Turbine\Contracts\DataSourceProcessor;
use PowerComponents\Turbine\DataSource\Processors\DataSourceBase;

class CustomApiProcessor extends DataSourceBase implements DataSourceProcessor
{
    /**
     * Determine if this processor handles the given datasource.
     */
    public static function match(mixed $datasource): bool
    {
        return $datasource instanceof MyCustomClient || $datasource instanceof MyCustomRepository;
    }

    /**
     * Resolve optional table/entity identifier name for metadata.
     */
    public function resolveTable(mixed $datasource): ?string
    {
        return 'custom_api_source';
    }

    /**
     * Process query, filters, sorting and pagination.
     */
    public function process(array $properties = [], mixed $datasource = null): array
    {
        $source = $datasource ?? $this->component->datasource($properties);

        // Fetch data from custom API or repository...
        $response = $source->fetchResults(
            page: request('page', 1),
            perPage: 15
        );

        $paginator = new LengthAwarePaginator(
            items: $response->items(),
            total: $response->total(),
            perPage: 15,
            currentPage: request('page', 1)
        );

        return [
            'results' => $paginator,
            'actionsByRow' => [],
        ];
    }
}
```

#### 2. Registration Possibilities

You can register custom DataSource processors in three different ways:

##### Option A: Fluent API (Boot or Service Provider)
```php
use PowerComponents\Turbine\Turbine;

// Registers the processor with top priority
Turbine::registerDataSource(CustomApiProcessor::class);
```

##### Option B: Config File (`config/turbine.php`)
```php
return [
    'max_per_page' => 1000,

    'datasources' => [
        App\DataSources\CustomApiProcessor::class,
        PowerComponents\Turbine\DataSource\Processors\CollectionProcessor::class,
        PowerComponents\Turbine\DataSource\Processors\ScoutBuilderProcessor::class,
        PowerComponents\Turbine\DataSource\Processors\ModelProcessor::class,
    ],
];
```

##### Option C: Service Provider Container Binding
```php
use PowerComponents\Turbine\DataSource\DataSourceManager;

public function boot(): void
{
    app(DataSourceManager::class)->register(CustomApiProcessor::class, prepend: true);
}
```

## Reusing an existing component

Already have a Turbine component (from a companion framework package)? It *is* a valid engine context — expose it as JSON without rewriting anything:

```php
return (new UserTable())->toDataResponse($request);   // JsonResponse
// or ->toDataArray($request) for the raw array
```

## Lower-level API

`Turbine` is a thin builder over two primitives. Drop to them when you need full control (custom context, caching, a bespoke response shape):

```php
use PowerComponents\Turbine\Response;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};

$context = new ArrayGridContext(
    state: State::fromRequest($request),   // reads the `turbine` key
    datasourceResolver: fn () => User::query(),
    fields: $fields,        // Fields
    columns: $columns,      // Column[]
    filters: $filters,      // Filter*[]
);

$envelope = Response::make($context)->toArray();
```

`Context` is the single interface the engine consumes; `ArrayGridContext` is the headless implementation. Implement it yourself for anything exotic.

## License

MIT
