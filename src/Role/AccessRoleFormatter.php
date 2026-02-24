<?php

namespace Ema\AccessBundle\Role;

class AccessRoleFormatter
{

    // TODO: read/write permission
    // if (!$attribute->rw) {
    //   return $role;
    // }
    // return $role . ($request->isMethodSafe() ? '_read' : '_write');
    public static function from(string $className, ?string $methodName = null): string
    {
        $role = 'EAB_' . \str_replace('\\', '', $className);
        if ($methodName !== null) {
            $role .= '_' . $methodName;
        }
        return $role;
    }
}
