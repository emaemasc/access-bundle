<?php

namespace Ema\AccessBundle\Role;

class DefaultAccessRoleStore extends AbstractAccessRoleStore
{
    public function __construct() {
        $this->setEntityClass(\Ema\AccessBundle\Entity\AccessRole::class);
    }
}
