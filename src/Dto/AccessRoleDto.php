<?php

namespace Ema\AccessBundle\Dto;

final class AccessRoleDto
{
    public function __construct(
        public string  $name,
        public string  $title,
        public ?array  $props,
        public ?string $group,
        public ?array  $presets,
    ) {
    }

    public static function new(string $name, string $title, ?array $props, ?string $group, ?array $presets): self
    {
        return new self($name, $title, $props, $group, $presets);
    }

    public function getName(): string
    {
        return $this->name;
    }

    public function setName(string $name): self
    {
        $this->name = $name;
        return $this;
    }

    public function getTitle(): string
    {
        return $this->title;
    }

    public function setTitle(string $title): self
    {
        $this->title = $title;
        return $this;
    }

    public function getProps(): ?array
    {
        return $this->props;
    }

    public function setProps(?array $props): self
    {
        $this->props = $props;
        return $this;
    }

    public function getProp(string $name): mixed
    {
        return $this->props[$name] ?? null;
    }

    public function getGroup(): ?string
    {
        return $this->group;
    }

    public function setGroup(?string $group): self
    {
        $this->group = $group;
        return $this;
    }

    public function getPresets(): ?array
    {
        return $this->presets;
    }

    public function setPresets(?array $presets): self
    {
        $this->presets = $presets;
        return $this;
    }
}
