<?php

namespace Ema\AccessBundle\Group;

use Ema\AccessBundle\Config\AccessConfigTrait;
use Ema\AccessBundle\Contracts\AccessGroupConfig;

abstract class AbstractAccessGroupConfig implements AccessGroupConfig
{
    use AccessConfigTrait;
}
