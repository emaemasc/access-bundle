<?php

namespace Ema\AccessBundle\Contracts;

use Ema\AccessBundle\Dto\AccessGroupDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface AccessGroupConfig
{
    /**
     * @return array<string, AccessGroupDto>
     */
    public function all(): array;

    /**
     * @return AccessGroupDto
     */
    public function get(string $key);
}
