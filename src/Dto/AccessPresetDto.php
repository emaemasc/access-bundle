<?php

namespace Ema\AccessBundle\Dto;

final class AccessPresetDto
{
    public array $items = [];

    public function __construct(
        /**
         * The name of access group.
         */
        public string $name,
        /**
         * The title of access group.
         */
        public string $title,
        /**
         * Sort index.
         */
        public int $sort = 100,
    ) {
    }

    public static function new(string $name, string $title, int $sort = 100): self
    {
        return new self($name, $title, $sort);
    }
}
