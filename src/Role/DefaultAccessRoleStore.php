<?php

namespace Ema\AccessBundle\Role;

use Ema\AccessBundle\Entity\AccessRole;

class DefaultAccessRoleStore extends AbstractAccessRoleStore
{
    public function getEntityClass(): string
    {
        return AccessRole::class;
    }

    public function getSuperRoles(): array
    {
        return [];
    }
}