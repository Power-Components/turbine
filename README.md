# Turbine

The framework-agnostic data engine behind the Turbine table component.

It runs **search, filters, sort, pagination, and row transformations** over Eloquent, Query Builder, Collections, or Scout, and returns a plain **JSON envelope**. There is no Blade and no JavaScript in the box — feed Inertia (React / Vue), Livewire, REST APIs, or plain AJAX with the exact same engine.

> **You describe the grid once in PHP. The engine does the rest. Your front-end just renders JSON.**

## Contents

- [Requirements & Install](#requirements--install)
- [How it fits together](#how-it-fits-together)
- [Quickstart](#quickstart)
- [Portable Grid Definitions](#portable-grid-definitions)
- [The Request Contract](#the-request-contract)
- [The Response Envelope](#the-response-envelope)
- [Actions & Rules](#actions--rules)
- [Filters](#filters)
- [Datasources](#datasources)
- [Exporting Data](#exporting-data)
- [State Persistence](#state-persistence)
- [Reusing Components & Low-Level API](#reusing-components--low-level-api)
- [Credits](#credits)
- [License](#license)

## Requirements & Install

- PHP 8.3+
- Laravel 12 / 13 (`illuminate/*` components)

```bash
composer require power-components/turbine
```

## How it fits together

```
                 ?turbine[...] + ?page
Front-end  ───────────────────────────────▶  Turbine (PHP definition)
(React / Vue /                                     │
 Livewire / AJAX)                                  ▼
                                             Turbine engine
                                   search · filter · sort · paginate
                                                   │
           JSON envelope { data, meta, columns, filters, actions }
Front-end  ◀───────────────────────────────────────┘
renders table
```

The front-end owns **rendering and interaction**; the core owns **data and rules**. State travels in the request; results travel in the envelope.

## Quickstart

Describe your grid in PHP using the `Turbine` builder:

```php
use PowerComponents\Turbine\{Turbine, Column, Fields, Button};
use PowerComponents\Turbine\Components\Filters\FilterInputText;

class UserGridController
{
    public function __invoke(Request $request)
    {
        return Turbine::make()
            ->datasource(fn () => User::query())
            ->fields(
                (new Fields())
                    ->add('id')
                    ->add('name')
                    ->add('email')
            )
            ->columns([
                Column::make('ID', 'id')->sortable(),
                Column::make('Name', 'name')->searchable()->sortable(),
                Column::make('Email', 'email')->searchable()->sortable(),
            ])
            ->filters([
                new FilterInputText('name'),
            ])
            ->actions(fn (User $user) => [
                Button::add('edit')->slot('Edit')->route('users.edit', ['user' => $user->id]),
            ])
            ->fromRequest($request)
            ->toArray(); // or ->toResponse() for JsonResponse
    }
}
```

```php
// routes/web.php or routes/api.php
Route::get('/users/grid', UserGridController::class);
```

### Builder reference

| Method | Description |
|---|---|
| `datasource(Closure)` | Returns Eloquent Builder, Scout Builder, or Collection. **Required.** |
| `fields(Fields)` | Maps row shape and output keys. |
| `columns(array)` | List of `Column` instances for schema, sorting, and search. |
| `filters(array)` | List of `Filter*` components. |
| `actions(Closure)` | `fn ($row) => Button[]` — per-row actions. |
| `actionRules(Closure)` | `fn ($row) => Rule[]` — conditional rules per row/action/cell. |
| `relationSearch(array)` | Search across relations, e.g. `['category' => ['name']]`. |
| `fromRequest(Request)` | Reads state from the `turbine` request parameter. |
| `setUp(array)` | List of SetUp components (`Header`, `Footer`, `Detail`, `Exportable`, …) serialized under `meta.setup`. |
| `state(array)` | Sets state from a raw array (Inertia JSON body, tests, etc.). |
| `toArray()` / `toResponse()` | Output as array or `JsonResponse`. |

## Portable Grid Definitions

The builder is fluent, but you can also describe a grid as a **single framework-neutral class** by extending `GridDefinition`. The same class drives an Inertia controller, a REST endpoint, or a Livewire PowerGrid component — write it once, switch front-ends without rewriting the grid.

```php
use PowerComponents\Turbine\{GridDefinition, Column, Fields, Turbine};

class UsersGrid extends GridDefinition
{
    public string $tableName = 'users';

    public int $perPage = 10;

    public function datasource(): mixed
    {
        return User::query();
    }

    public function fields(): Fields
    {
        return Fields::make()->add('id')->add('name')->add('email');
    }

    public function columns(): array
    {
        return [
            Column::make('ID', 'id')->sortable(),
            Column::make('Name', 'name')->searchable()->sortable(),
        ];
    }

    public function setUp(): array
    {
        return [Turbine::footer()->showPerPage(10, [10, 25, 50])];
    }
}
```

Build fields and SetUp components with the static factories — `Fields::make()` and `Turbine::header() / footer() / detail() / exportable($file) / cache() / filterBuilder() / responsive()`. These are the **same construction calls** the Livewire PowerGrid facade exposes (`PowerGrid::footer()`), so a definition reads identically on both stacks.

Consume it from an Inertia controller:

```php
public function __invoke(Request $request)
{
    $grid = new UsersGrid();

    return Inertia::render('users', [
        'columns' => fn () => $grid->columns(),
        'grid' => fn () => $grid->toArray($request),
    ]);
}
```

Every builder option has a matching overridable method — `datasource`, `fields`, `columns`, `filters`, `actions`, `actionRules`, `relationSearch`, `searchMorphs`, `transformQuery`, `setUp` — plus the `$tableName`, `$primaryKey`, `$perPage`, `$pageName` properties. `toArray($request)`, `toResponse($request)` and `context($request)` (for [exporting](#exporting-data)) feed the request in for you. Only `datasource()` is required.

The class implements `PowerComponents\Turbine\Contracts\GridSchema`, the shared declaration surface both Turbine and Livewire PowerGrid understand.

> **Migrating between Livewire and Inertia?** Keep the `UsersGrid` class untouched and swap only the adapter. Inertia calls `->toArray($request)`; a Livewire PowerGrid component points its `definition()` at the same class (see the [PowerGrid README](https://github.com/Power-Components/livewire-powergrid#reusing-a-grid-definition)). Columns, fields, filters, actions and setUp are identical on both sides — the difference is purely the wiring.

## The Request Contract

Grid state is passed via the `turbine` query parameter and standard `page`:

| Parameter | Purpose | Example |
|---|---|---|
| `turbine[search]` | Global search term | `turbine[search]=maria` |
| `turbine[sortField]` | Column to sort by | `turbine[sortField]=name` |
| `turbine[sortDirection]` | Direction (`asc` / `desc`) | `turbine[sortDirection]=desc` |
| `turbine[filters][<key>][<field>]` | Filter value by type and field | `turbine[filters][input_text][name]=ana` |
| `page` | Page number | `page=2` |

## The Response Envelope

`->toArray()` or `->toResponse()` returns a structured JSON payload:

```jsonc
{
  "data": [
    { "id": 1, "name": "Ana", "email": "ana@acme.test" }
  ],
  "meta": {
    "pagination": { "current_page": 1, "per_page": 15, "total": 84, "last_page": 6 },
    "sort": { "field": "name", "direction": "desc" },
    "search": "ana",
    "filters": { "input_text": { "name": "ana" } },
    "setup": { "footer": { "name": "footer", "perPage": 10, "perPageValues": [10, 25, 50], "pageName": "page" } }
  },
  "columns": [
    { "field": "name", "title": "Name", "sortable": true, "searchable": true }
  ],
  "filters": [
    { "key": "input_text", "field": "name", "column": "name" }
  ],
  "actions": {
    "1": [
      { "id": "edit", "label": "Edit", "visible": true, "disabled": false, "event": { "type": "link", "href": "/users/1/edit" } }
    ]
  }
}
```

## Actions & Rules

Actions use the `Button` DSL and resolve on the server:

```php
Button::add('edit')->slot('Edit')->route('users.edit', ['user' => $user->id]);
Button::add('delete')->slot('Delete')->dispatch('deleteUser', ['id' => $user->id])->confirm('Are you sure?');
```

Event types sent to client: `link`, `dispatch`, `dispatchTo`, `dispatchSelf`, `modal`, `toggleDetail`, `call`.

### Conditional Rules

Apply server-side rules per row, button, or cell:

```php
use PowerComponents\Turbine\Components\Rules\{RuleActions, RuleRows, RuleCheckbox, RuleToggleable};

Turbine::make()
    ->actionRules(fn (User $user) => [
        // Hide delete action for admins
        (new RuleActions('delete'))->when(fn ($u) => $u->is_admin)->hide(),

        // Highlight admin rows
        (new RuleRows())->when(fn ($u) => $u->is_admin)->setAttribute('class', 'bg-blue-50'),

        // Disable checkboxes for system users
        (new RuleCheckbox())->when(fn ($u) => $u->is_system)->disable(),
    ]);
```

Supported rule target classes: `RuleActions`, `RuleRows`, `RuleCheckbox`, `RuleRadio`, `RuleToggleable`, `RuleEditOnClick`.

## Filters

Turbine provides built-in filter components in `PowerComponents\Turbine\Components\Filters\*`:

| Filter Class | Request Key | Description |
|---|---|---|
| `FilterInputText` | `input_text` | Text matching (`contains`, `starts_with`, `exact`, etc.) |
| `FilterSelect` / `FilterEnumSelect` | `select` | Single choice from collection, array, or PHP Enum |
| `FilterMultiSelect` / `FilterMultiSelectAsync` | `multi_select` | Multiple choices in-memory or from async endpoint |
| `FilterBoolean` | `boolean` | True / false toggle |
| `FilterNumber` | `number` | Numeric range filter |
| `FilterDatePicker` / `FilterDateTimePicker` | `date` / `datetime` | Date and date-time range filters |
| `FilterDynamic` | `custom` | Custom front-end component props |

```php
// Standard filter usage
new FilterSelect('status')->dataSource(UserStatusEnum::cases());

// Custom query logic callback
new FilterInputText('title')->builder(fn ($query, $value) => $query->whereRaw('LOWER(title) LIKE ?', ["%{$value}%"]));
```

## Datasources

Pass any supported data source to `datasource()`:

```php
// Eloquent Query Builder or Model
Turbine::make()->datasource(fn () => User::query());

// Laravel Scout
Turbine::make()->datasource(fn () => User::search($term));

// Array or Collection
Turbine::make()->datasource(fn () => collect([['id' => 1, 'name' => 'Alice']]));
```

### Custom Datasources

Implement `DataSourceProcessor` to handle custom APIs or repositories:

```php
use PowerComponents\Turbine\Contracts\DataSourceProcessor;
use PowerComponents\Turbine\DataSource\Processors\DataSourceBase;

class CustomApiProcessor extends DataSourceBase implements DataSourceProcessor
{
    public static function match(mixed $datasource): bool => $datasource instanceof MyCustomClient;

    public function process(array $properties = [], mixed $datasource = null): array
    {
        // Fetch data, return length-aware paginator in results
        return ['results' => $paginator, 'actionsByRow' => []];
    }
}

// Register globally
Turbine::registerDataSource(CustomApiProcessor::class);
```

## Exporting Data

Generate CSV or Excel (`.xlsx`) files directly from your grid context:

```bash
composer require openspout/openspout # Optional: required for XLSX exports
```

```php
use PowerComponents\Turbine\Export\ExportEngine;

$filePath = app(ExportEngine::class)->build(
    context: $turbine->context(),
    exportType: 'xlsx', // 'xlsx' or 'csv'
    fileName: 'users_export'
);

return response()->download($filePath)->deleteFileAfterSend(true);
```

## State Persistence

Save grid state (filters, sorting, column visibility) across requests:

```php
use PowerComponents\Turbine\Support\State\StatePersister;

$persister = new StatePersister();

// Save state to Cookie, Session, or Cache
$persister->serializeState(['columns', 'filters', 'sorting'], 'users', $stateArray);

// Restore saved state
$savedState = $persister->getPersistedState('users');
```

## Reusing Components & Low-Level API

Re-use an existing Turbine component as a data engine endpoint:

```php
return (new UserTable())->toDataResponse($request);
```

Or drop down to low-level context primitives:

```php
use PowerComponents\Turbine\Response;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};

$context = new ArrayGridContext(
    state: State::fromRequest($request),
    datasourceResolver: fn () => User::query(),
    fields: $fields,
    columns: $columns,
);

$envelope = Response::make($context)->toArray();
```

## Credits

Originally extracted from versions **6.x** and **7.x** of [Livewire PowerGrid](https://github.com/Power-Components/livewire-powergrid). Special thanks to all contributors!

## License

MIT
