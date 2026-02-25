<?php

namespace Ema\AccessBundle\Contracts;

use Ema\AccessBundle\Dto\AccessRoleDto;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface AccessRoleStore
{
    public function getEntityClass(): string;

    public function getSuperRoles(): array;
    /**
     * @return AccessRoleDto[]
     */
    public function getRoles(): array;

    public function findBy(array $params): array;
    
    /**
     * Clears the role cache if caching is enabled
     */
    public function clearCache(): void;
}