<?php

namespace PowerComponents\Turbine;

use Countable;
use Illuminate\Support\{Arr, LazyCollection};
use stdClass;

/**
 * Blade-compatible loop bookkeeping, reimplemented without illuminate/view so
 * the core carries no view dependency. Mirrors the shape of Laravel's `$loop`
 * variable (index, first, last, count, ...) attached to each transformed row.
 *
 * @phpstan-type LoopEntry array{iteration: int, index: int, remaining: int|null, count: int|null, first: bool, last: bool|null, odd: bool, even: bool, depth: int, parent: stdClass|null}
 */
class ManageLoops
{
    /** @var list<LoopEntry> */
    protected array $loopsStack = [];

    /** @param  Countable|array<array-key, mixed>  $data */
    public function addLoop(Countable|array $data): void
    {
        $length = $data instanceof LazyCollection ? null : count($data);

        $parent = Arr::last($this->loopsStack);

        $this->loopsStack[] = [
            'iteration' => 0,
            'index' => 0,
            'remaining' => $length,
            'count' => $length,
            'first' => true,
            'last' => $length !== null ? $length === 1 : null,
            'odd' => false,
            'even' => true,
            'depth' => count($this->loopsStack) + 1,
            'parent' => $parent !== null ? (object) $parent : null,
        ];
    }

    public function incrementLoopIndices(): void
    {
        $index = count($this->loopsStack) - 1;
        $loop = $this->loopsStack[$index];

        $iteration = $loop['iteration'];
        $count = $loop['count'];

        $this->loopsStack[$index] = [
            'iteration' => $iteration + 1,
            'index' => $iteration,
            'remaining' => $count !== null ? $count - $iteration - 1 : null,
            'count' => $count,
            'first' => $iteration === 0,
            'last' => $count !== null ? $iteration === $count - 1 : null,
            'odd' => ! $loop['odd'],
            'even' => ! $loop['even'],
            'depth' => $loop['depth'],
            'parent' => $loop['parent'],
        ];
    }

    public function popLoop(): void
    {
        array_pop($this->loopsStack);
    }

    public function getLastLoop(): ?stdClass
    {
        $last = Arr::last($this->loopsStack);

        return $last !== null ? (object) $last : null;
    }

    /** @return list<LoopEntry> */
    public function getLoopStack(): array
    {
        return $this->loopsStack;
    }
}
