<?php

namespace Ema\AccessBundle\Config;

use Ema\AccessBundle\Dto\AccessGroupDto;
use Ema\AccessBundle\Dto\AccessPresetDto;

trait AccessConfigTrait
{
    private array $items = [];

    public function all(): array
    {
        return $this->items;
    }

    public function get(string $key): AccessGroupDto|AccessPresetDto|null
    {
        return $this->items[$key] ?? null;
    }

    public function has(string $key): bool
    {
        return \array_key_exists($key, $this->items);
    }

    public function set(string $key, AccessGroupDto|AccessPresetDto|null $value = null): self
    {
        $this->items[$key] = $value;
        return $this;
    }
}
