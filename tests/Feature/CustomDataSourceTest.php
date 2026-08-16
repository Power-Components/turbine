<?php

namespace PowerComponents\Turbine\Tests\Feature;

use Illuminate\Pagination\LengthAwarePaginator;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use PowerComponents\Turbine\{Column, Fields, Turbine};
use PowerComponents\Turbine\Contracts\DataSourceProcessor;
use PowerComponents\Turbine\DataSource\DataSourceManager;
use PowerComponents\Turbine\DataSource\Processors\{CollectionProcessor, DataSourceBase, ModelProcessor};
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

class CustomSearchSource
{
    /** @param array<int, array<string, mixed>> $items */
    public function __construct(public array $items = []) {}
}

class CustomSearchProcessor extends DataSourceBase implements DataSourceProcessor
{
    public static function match(mixed $datasource): bool
    {
        return $datasource instanceof CustomSearchSource;
    }

    public function resolveTable(mixed $datasource): ?string
    {
        return 'custom_search_table';
    }

    public function process(array $properties = [], mixed $datasource = null): array
    {
        /** @var CustomSearchSource $source */
        $source = $datasource ?? $this->component->datasource($properties);

        $collection = new Collection($source->items);

        return [
            'results' => new LengthAwarePaginator(
                items: $collection,
                total: $collection->count(),
                perPage: 10,
                currentPage: 1
            ),
            'actionsByRow' => [],
        ];
    }
}

describe('Custom DataSource Extension', function () {
    it('registers custom processor fluently via Turbine::registerDataSource', function () {
        Turbine::registerDataSource(CustomSearchProcessor::class);

        /** @var DataSourceManager $manager */
        $manager = app(DataSourceManager::class);

        expect($manager->getProcessors())->toContain(CustomSearchProcessor::class);
    });

    it('processes custom datasource and produces expected results', function () {
        Turbine::registerDataSource(CustomSearchProcessor::class);

        $items = [
            ['id' => 1, 'name' => 'Custom Item 1'],
            ['id' => 2, 'name' => 'Custom Item 2'],
        ];

        $envelope = Turbine::make()
            ->datasource(fn () => new CustomSearchSource($items))
            ->fields((new Fields())->add('id')->add('name'))
            ->columns([Column::make('Name', 'name')])
            ->toArray();

        expect($envelope['data'])->toHaveCount(2)
            ->and($envelope['data'][0]['name'])->toBe('Custom Item 1')
            ->and($envelope['data'][1]['name'])->toBe('Custom Item 2');
    });

    it('resolves table from custom processor', function () {
        Turbine::registerDataSource(CustomSearchProcessor::class);

        /** @var DataSourceManager $manager */
        $manager = app(DataSourceManager::class);

        $table = $manager->resolveTable(new CustomSearchSource());

        expect($table)->toBe('custom_search_table');
    });

    it('loads custom processors from config', function () {
        config([
            'turbine.datasources' => [
                CustomSearchProcessor::class,
                CollectionProcessor::class,
                ModelProcessor::class,
            ],
        ]);

        $manager = new DataSourceManager();

        expect($manager->getProcessors())->toContain(CustomSearchProcessor::class);
    });

    it('flushes and resets registered processors', function () {
        $manager = new DataSourceManager();
        $manager->register(CustomSearchProcessor::class);

        expect($manager->getProcessors())->toContain(CustomSearchProcessor::class);

        $manager->flush();
        expect($manager->getProcessors())->toBeEmpty();

        $manager->reset();
        expect($manager->getProcessors())->not->toBeEmpty()
            ->and($manager->getProcessors())->not->toContain(CustomSearchProcessor::class);
    });

    it('caches processor class matching for performance', function () {
        $manager = new DataSourceManager();
        $manager->register(CustomSearchProcessor::class, prepend: true);
        $source = new CustomSearchSource();

        $processorClass1 = $manager->findProcessorClass($source);
        $processorClass2 = $manager->findProcessorClass($source);

        expect($processorClass1)->toBe(CustomSearchProcessor::class)
            ->and($processorClass2)->toBe(CustomSearchProcessor::class);
    });

    it('safely resolves table names from query builder with stringable or raw expressions', function () {
        $manager = new DataSourceManager();
        $builder = DB::table(DB::raw('users_custom'));

        $table = $manager->resolveTable($builder);

        expect($table)->toBe('users_custom');
    });

    it('resolves table name from collection containing models', function () {
        $manager = new DataSourceManager();
        $model = new Dish();
        $collection = collect([$model]);

        $table = $manager->resolveTable($collection);

        expect($table)->toBe('dishes');
    });
});
