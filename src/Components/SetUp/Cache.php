<?php

namespace PowerComponents\Turbine\Components\SetUp;

use PowerComponents\Turbine\Contracts\Definition;

final class Cache implements Definition
{
    public string $name = 'cache';

    public bool $enabled = true;

    public string $tag = '';

    public int $ttl = 300;

    public string $prefix = '';

    public function disabled(): Cache
    {
        $this->enabled = false;

        return $this;
    }

    public function customTag(string $tag): Cache
    {
        $this->tag = $tag;

        return $this;
    }

    public function prefix(string $prefix): Cache
    {
        $this->prefix = $prefix;

        return $this;
    }

    public function ttl(int $time): Cache
    {
        $this->ttl = $time;

        return $this;
    }
}
