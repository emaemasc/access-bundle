<?php

namespace Ema\AccessBundle\Form\DataTransformer;

use Doctrine\Common\Collections\ArrayCollection;
use Doctrine\Common\Collections\Collection;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Symfony\Component\Form\DataTransformerInterface;

class AccessRoleDataTransformer implements DataTransformerInterface
{
    public function __construct(
        private readonly AccessRoleStore $roleStore,
    ) {
    }

    public function transform(mixed $value): mixed
    {
        if ($value instanceof Collection) {
            foreach ($value->toArray() as $role) {
                $items[$role->getName()] = true;
            }
            return $items ?? [];
        }
        return $value;
    }

    public function reverseTransform(mixed $value): mixed
    {
        if (\is_array($value)) {
            $names = \array_keys(\array_filter($value, fn ($v) => $v === true));
            $roles = $this->roleStore->findBy(['name' => $names]);
            return new ArrayCollection($roles);
        }
        return $value;
    }
}
