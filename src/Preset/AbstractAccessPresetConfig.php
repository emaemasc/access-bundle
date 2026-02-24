<?php

namespace Ema\AccessBundle\Preset;

use Ema\AccessBundle\Config\AccessConfigTrait;
use Ema\AccessBundle\Contracts\AccessPresetConfig;

abstract class AbstractAccessPresetConfig implements AccessPresetConfig
{
    use AccessConfigTrait;
}
