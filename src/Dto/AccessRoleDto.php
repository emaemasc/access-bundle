<?php

namespace Ema\AccessBundle\Dto;

final class AccessRoleDto
{
    public function __construct(
        public string  $name,
        public string  $title,
        public ?array $options,
        public ?string $group,
        public ?array  $presets,
    ) {
    }

    public static function new(string $name, string $title, ?array $options, ?string $group, ?array $presets): self
    {
        return new self($name, $title, $options, $group, $presets);
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
