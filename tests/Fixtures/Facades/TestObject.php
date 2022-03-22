<?php

namespace Henzeb\Jukebox\Tests\Fixtures\Facades;

class TestObject
{
    private ?string $parameter = null;

    public function test(): string
    {
        return 'test';
    }

    public function set(?string $parameter): void
    {
        $this->parameter = $parameter;
    }

    public function get(): ?string
    {
        return $this->parameter;
    }
}
