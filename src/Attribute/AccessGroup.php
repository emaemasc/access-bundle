<?php

namespace Ema\AccessBundle\Attribute;

#[\Attribute(\Attribute::TARGET_CLASS | \Attribute::TARGET_METHOD | \Attribute::TARGET_FUNCTION)]
final class AccessGroup
{
    public function __construct(
        /**
         * The first argument of AccessGroup().
         */
        public string $name,
    ) {
    }
}
