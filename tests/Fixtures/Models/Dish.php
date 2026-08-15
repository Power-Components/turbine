<?php

namespace PowerComponents\Turbine\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;

/**
 * Minimal, relation-free fixture: the engine tests only need a real Eloquent
 * model backed by a table so the Model/Database processor path can run.
 *
 * @property int $id
 * @property string $name
 * @property float $price
 * @property bool $in_stock
 */
class Dish extends Model
{
    protected $table = 'dishes';

    protected $guarded = [];

    public $timestamps = false;

    protected $casts = [
        'price' => 'float',
        'in_stock' => 'boolean',
    ];
}
