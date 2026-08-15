<?php

namespace PowerComponents\Turbine\Tests;

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;
use Orchestra\Testbench\TestCase as BaseTestCase;
use PowerComponents\Turbine\Providers\TurbineServiceProvider;
use PowerComponents\Turbine\Tests\Fixtures\Models\Dish;

abstract class TestCase extends BaseTestCase
{
    protected function getPackageProviders($app): array
    {
        return [
            TurbineServiceProvider::class,
        ];
    }

    protected function defineEnvironment($app): void
    {
        $app['config']->set('database.default', 'testing');
        $app['config']->set('database.connections.testing', [
            'driver' => 'sqlite',
            'database' => ':memory:',
            'prefix' => '',
        ]);
    }

    protected function defineDatabaseMigrations(): void
    {
        Schema::create('dishes', function (Blueprint $table): void {
            $table->id();
            $table->string('name');
            $table->float('price');
            $table->boolean('in_stock')->default(true);
        });

        Dish::query()->insert([
            ['name' => 'Sushi', 'price' => 30.0, 'in_stock' => true],
            ['name' => 'Pastel de Nata', 'price' => 10.0, 'in_stock' => true],
            ['name' => 'Pastel de Belém', 'price' => 20.0, 'in_stock' => false],
            ['name' => 'Pizza', 'price' => 45.0, 'in_stock' => true],
            ['name' => 'Coxinha', 'price' => 8.0, 'in_stock' => false],
        ]);
    }
}
