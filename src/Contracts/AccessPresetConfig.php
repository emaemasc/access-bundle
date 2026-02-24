<?php

namespace Ema\AccessBundle\Contracts;

use Ema\AccessBundle\Dto\AccessPresetDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface AccessPresetConfig
{
    /**
     * @return array<string, AccessPresetDto>
     */
    public function all(): array;

    /**
     * @return AccessPresetDto
     */
    public function get(string $key);
}
