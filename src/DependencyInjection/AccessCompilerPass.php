<?php

namespace Ema\AccessBundle\DependencyInjection;

use Ema\AccessBundle\Attribute\Access;
use Ema\AccessBundle\Attribute\AccessGroup;
use Ema\AccessBundle\Attribute\AccessPreset;
use Ema\AccessBundle\Attribute\AsAccessGroupConfig;
use Ema\AccessBundle\Attribute\AsAccessPresetConfig;
use Ema\AccessBundle\Attribute\AsAccessRoleStore;
use Ema\AccessBundle\Contracts\AccessGroupConfig;
use Ema\AccessBundle\Contracts\AccessPresetConfig;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Role\AccessRoleFormatter;
use PHPUnit\Framework\Attributes\Group;
use Symfony\Component\DependencyInjection\Compiler\CompilerPassInterface;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AccessCompilerPass implements CompilerPassInterface
{
    public function process(ContainerBuilder $container): void
    {
        $roleStore = $container->findDefinition(AccessRoleStore::class);

        foreach ($container->getDefinitions() as $id => $definition) {
            if ($definition->getClass() === null) {
                continue;
            }

            $reflectionClass = $container->getReflectionClass($definition->getClass(), false);
            if (null === $reflectionClass) {
                continue;
            }

            $accessClass = $reflectionClass->getAttributes(Access::class);
            $classGroups = $reflectionClass->getAttributes(AccessGroup::class);
            $classPresets = $reflectionClass->getAttributes(AccessPreset::class);
            $classGroupName = empty($classGroups) ? null : $classGroups[0]->newInstance()->name;
            $classPresetNames = \array_map(fn (\ReflectionAttribute $attribute) => $attribute->newInstance()->name, $classPresets);

            if (!empty($accessClass)) {
                $accessClassAttribute = $accessClass[0]->newInstance();

                $roleStore->addMethodCall('addRole', [
                    AccessRoleFormatter::from($definition->getClass()),
                    $accessClassAttribute->title,
                    $accessClassAttribute->options,
                    $classGroupName,
                    $classPresetNames
                ]);
            }

            foreach ($reflectionClass->getMethods() as $method) {
                $accessMethod = $method->getAttributes(Access::class);
                if (empty($accessMethod)) {
                    continue;
                }

                $methodGroups = $method->getAttributes(AccessGroup::class);
                $methodPresets = $method->getAttributes(AccessPreset::class);

                $accessMethodAttribute = $accessMethod[0]->newInstance();
                $roleStore->addMethodCall('addRole', [
                    AccessRoleFormatter::from($definition->getClass(), $method->name),
                    $accessMethodAttribute->title,
                    $accessMethodAttribute->options,
                    empty($methodGroups) ? $classGroupName : $methodGroups[0]->newInstance()->name,
                    empty($methodPresets) ? $classPresetNames : \array_map(fn (\ReflectionAttribute $attribute) => $attribute->newInstance()->name, $methodPresets),
                ]);
            }
        }
    }
}
