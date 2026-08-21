<?php

namespace PowerComponents\Turbine\Tests\Feature;

use PowerComponents\Turbine\Components\SetUp\{
    Cache,
    Detail,
    Exportable,
    FilterBuilder,
    Footer,
    Header,
    HeaderElement
};

it('configures Header component settings', function () {
    $header = new Header();

    expect($header->searchInput)->toBeFalse()
        ->and($header->toggleColumns)->toBeFalse()
        ->and($header->softDeletes)->toBeFalse()
        ->and($header->wireLoading)->toBeTrue();

    $header->showSearchInput()
        ->showToggleColumns()
        ->showSoftDeletes(true)
        ->includeViewOnTop('header.top')
        ->includeViewOnBottom('header.bottom')
        ->withoutLoading();

    expect($header->searchInput)->toBeTrue()
        ->and($header->toggleColumns)->toBeTrue()
        ->and($header->softDeletes)->toBeTrue()
        ->and($header->showMessageSoftDeletes)->toBeTrue()
        ->and($header->includeViewOnTop)->toBe('header.top')
        ->and($header->includeViewOnBottom)->toBe('header.bottom')
        ->and($header->wireLoading)->toBeFalse();
});

it('configures Footer component settings', function () {
    $footer = new Footer();

    $footer->showPerPage(15, [10, 15, 20])
        ->showRecordCount('short')
        ->pagination('pagination.custom')
        ->includeViewOnTop('footer.top')
        ->includeViewOnBottom('footer.bottom')
        ->pageName('p_page!');

    expect($footer->perPage)->toBe(15)
        ->and($footer->perPageValues)->toBe([10, 15, 20])
        ->and($footer->recordCount)->toBe('short')
        ->and($footer->pagination)->toBe('pagination.custom')
        ->and($footer->includeViewOnTop)->toBe('footer.top')
        ->and($footer->includeViewOnBottom)->toBe('footer.bottom')
        ->and($footer->pageName)->toBe('ppage');
});

it('adds missing perPage value into Footer perPageValues correctly', function () {
    $footer = new Footer();
    $footer->showPerPage(15, [10, 25, 50, 0]);

    expect($footer->perPageValues)->toBe([10, 15, 25, 50, 0]);
});

it('configures Cache component settings', function () {
    $cache = new Cache();

    expect($cache->enabled)->toBeTrue()
        ->and($cache->ttl)->toBe(300);

    $cache->disabled()
        ->customTag('grid_tag')
        ->prefix('grid_prefix')
        ->ttl(600);

    expect($cache->enabled)->toBeFalse()
        ->and($cache->tag)->toBe('grid_tag')
        ->and($cache->prefix)->toBe('grid_prefix')
        ->and($cache->ttl)->toBe(600);
});

it('configures Detail component settings', function () {
    $detail = new Detail();

    $detail->view('components.detail')
        ->params(['foo' => 'bar'])
        ->showCollapseIcon('icon-view')
        ->singleExpand();

    expect($detail->view)->toBe('components.detail')
        ->and($detail->options)->toBe(['foo' => 'bar'])
        ->and($detail->showCollapseIcon)->toBeTrue()
        ->and($detail->viewIcon)->toBe('icon-view')
        ->and($detail->singleExpand)->toBeTrue();

    // Deprecated helpers
    $detail->options(['a' => 'b']);
    expect($detail->options)->toBe(['a' => 'b']);

    $detail->collapseOthers();
    expect($detail->singleExpand)->toBeTrue();
});

it('configures Exportable component settings', function () {
    $exportable = new Exportable('dishes');

    expect($exportable->fileName)->toBe('dishes');

    $exportable->type(Exportable::TYPE_XLS, Exportable::TYPE_CSV)
        ->csvSeparator(';')
        ->csvDelimiter("'")
        ->striped('#ffffff')
        ->columnWidth(['a' => 10])
        ->deleteFileAfterSend(false)
        ->disk('s3')
        ->directory('exports')
        ->queryOptions(['limit' => 1000])
        ->stripTags(true);

    expect($exportable->type)->toBe(['xlsx', 'csv'])
        ->and($exportable->csvSeparator)->toBe(';')
        ->and($exportable->csvDelimiter)->toBe("'")
        ->and($exportable->striped)->toBe('#ffffff')
        ->and($exportable->columnWidth)->toBe(['a' => 10])
        ->and($exportable->deleteFileAfterSend)->toBeFalse()
        ->and($exportable->disk)->toBe('s3')
        ->and($exportable->directory)->toBe('exports')
        ->and($exportable->queryOptions)->toBe(['limit' => 1000])
        ->and($exportable->stripTags)->toBeTrue();
});

it('configures FilterBuilder component settings', function () {
    $builder = new FilterBuilder();

    expect($builder->match)->toBe('and')
        ->and($builder->maxConditions)->toBe(30);

    $builder->match('or')
        ->maxConditions(5)
        ->hideDefaultFilters()
        ->persist()
        ->only(['name', 'price'])
        ->except(['id']);

    expect($builder->match)->toBe('or')
        ->and($builder->maxConditions)->toBe(5)
        ->and($builder->hideDefaultFilters)->toBeTrue()
        ->and($builder->persist)->toBeTrue()
        ->and($builder->only)->toBe(['name', 'price'])
        ->and($builder->except)->toBe(['id']);

    $builder->match('invalid');
    expect($builder->match)->toBe('and');

    $builder->maxConditions(0);
    expect($builder->maxConditions)->toBe(1);
});

it('configures header elements icons and titles', function () {
    $header = new Header();

    $header->showToggleColumns(fn (HeaderElement $element) => $element->icon('funnel', ['class' => 'size-4'])->title('Columns')->showLabel())
        ->showSoftDeletes(config: fn (HeaderElement $element) => $element->withoutIcon())
        ->showSearchInput()
        ->filtersToggle(fn (HeaderElement $element) => $element->view('custom.filters'))
        ->clearFiltersPill(fn (HeaderElement $element) => $element->hideLabel())
        ->searchClearIcon(fn (HeaderElement $element) => $element->icon('x-mark'));

    expect($header->elements['toggleColumns']->icon)->toBe('funnel')
        ->and($header->elements['toggleColumns']->iconAttributes)->toBe(['class' => 'size-4'])
        ->and($header->elements['toggleColumns']->title)->toBe('Columns')
        ->and($header->elements['toggleColumns']->showLabel)->toBeTrue()
        ->and($header->elements['softDeletes']->iconDisabled)->toBeTrue()
        ->and($header->elements['search']->icon)->toBe('')
        ->and($header->elements['search']->showLabel)->toBeNull()
        ->and($header->elements['filters']->view)->toBe('custom.filters')
        ->and($header->elements['clearFilters']->showLabel)->toBeFalse()
        ->and($header->elements['searchClear']->icon)->toBe('x-mark');
});

it('configures the filter builder and export triggers', function () {
    $filterBuilder = (new FilterBuilder())->title('Filter')->icon('funnel');

    $exportable = (new Exportable('report'))->trigger(fn (HeaderElement $element) => $element->icon('arrow-down')->showLabel());

    expect($filterBuilder->trigger->title)->toBe('Filter')
        ->and($filterBuilder->trigger->icon)->toBe('funnel')
        ->and($filterBuilder->trigger->key)->toBe('filterBuilder')
        ->and($exportable->trigger->icon)->toBe('arrow-down')
        ->and($exportable->trigger->showLabel)->toBeTrue()
        ->and($exportable->trigger->key)->toBe('export');
});
