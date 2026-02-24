<?php

namespace Ema\AccessBundle\Attribute;

#[\Attribute(\Attribute::IS_REPEATABLE | \Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
final class AccessPreset
{
    public function __construct(
        /**
         * The first argument of AccessPreset().
         */
        public string $name,
    ) {
    }
}
