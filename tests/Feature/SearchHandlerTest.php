<?php

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use PowerComponents\Turbine\{Column, Fields};
use PowerComponents\Turbine\DataSource\Processors\Database\Handlers\SearchHandler;
use PowerComponents\Turbine\Support\State\{ArrayGridContext, State};

class SearchOwner extends Model
{
    protected $table = 'search_owners';

    public $timestamps = false;

    public function notes(): HasMany
    {
        return $this->hasMany(SearchNote::class, 'owner_id');
    }
}

class SearchNote extends Model
{
    protected $table = 'search_notes';

    public $timestamps = false;
}

it('keeps relation search inside the datasource where', function () {
    Schema::create('search_owners', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('tenant_id');
        $table->string('name');
    });
    Schema::create('search_notes', function (Blueprint $table): void {
        $table->id();
        $table->unsignedBigInteger('owner_id');
        $table->string('body');
    });

    $context = new ArrayGridContext(
        state: State::fromArray(['search' => 'secret', 'primaryKey' => 'id', 'tableName' => 'search_owners']),
        datasourceResolver: fn () => SearchOwner::query(),
        fields: (new Fields())->add('id')->add('name'),
        columns: [Column::make('Name', 'name')->searchable()],
        relationSearch: ['notes' => ['body']],
    );

    $query = SearchOwner::query()->where('tenant_id', 1);
    (new SearchHandler($context))->apply($query);

    $sql = strtolower($query->toSql());

    expect($sql)->toContain('and ("search_owners"."name" like ? or exists')
        ->and($sql)->not->toContain('like ?) or exists');
});
