<?php

namespace Ema\AccessBundle\Tests\Unit\DependencyInjection;

use Ema\AccessBundle\DependencyInjection\AccessCompilerPass;
use Ema\AccessBundle\Attribute\Access;
use Ema\AccessBundle\Attribute\AccessGroup;
use Ema\AccessBundle\Attribute\AccessPreset;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Contracts\AccessGroupConfig;
use Ema\AccessBundle\Contracts\AccessPresetConfig;
use Ema\AccessBundle\Role\AccessRoleFormatter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

#[AllowMockObjectsWithoutExpectations]
class AccessCompilerPassTest extends TestCase
{
    private ContainerBuilder|MockObject $container;
    private AccessCompilerPass $compilerPass;
    private Definition|MockObject $roleStoreDef;
    private Definition|MockObject $groupConfigDef;
    private Definition|MockObject $presetConfigDef;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerBuilder::class);
        $this->roleStoreDef = $this->createMock(Definition::class);
        $this->groupConfigDef = $this->createMock(Definition::class);
        $this->presetConfigDef = $this->createMock(Definition::class);
        $this->compilerPass = new AccessCompilerPass();
    }

    public function testProcessWithNoDefinitions(): void
    {
        $this->container->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([]);

        $this->container->expects($this->exactly(3))
            ->method('findDefinition')
            ->willReturnCallback(function ($id) {
                switch ($id) {
                    case AccessRoleStore::class:
                        return $this->roleStoreDef;
                    case AccessGroupConfig::class:
                        return $this->groupConfigDef;
                    case AccessPresetConfig::class:
                        return $this->presetConfigDef;
                    default:
                        throw new \InvalidArgumentException("Unexpected service ID: $id");
                }
            });

        $this->roleStoreDef->expects($this->once())
            ->method('addMethodCall')
            ->with('configure');
        $this->groupConfigDef->expects($this->once())
            ->method('addMethodCall')
            ->with('configure');
        $this->presetConfigDef->expects($this->once())
            ->method('addMethodCall')
            ->with('configure');

        $this->compilerPass->process($this->container);
    }

    public function testProcessWithClassWithoutAccessAttribute(): void
    {
        $definition = $this->createMock(Definition::class);
        $definition->method('getClass')
            ->willReturn(TestController::class);

        $this->container->expects($this->once())
            ->method('getDefinitions')
            ->willReturn(['test_service' => $definition]);

        $this->container->expects($this->exactly(3))
            ->method('findDefinition')
            ->willReturnCallback(function ($id) {
                return match ($id) {
                    AccessRoleStore::class => $this->roleStoreDef,
                    AccessGroupConfig::class => $this->groupConfigDef,
                    AccessPresetConfig::class => $this->presetConfigDef,
                    default => throw new \InvalidArgumentException("Unexpected service ID: $id"),
                };
            });

        $this->container->expects($this->once())
            ->method('getReflectionClass')
            ->with(TestController::class, false)
            ->willReturn(new \ReflectionClass(TestController::class));

        // All three services should have "configure" called
        $this->roleStoreDef->expects($this->once())
            ->method('addMethodCall')
            ->with('configure');
        $this->groupConfigDef->expects($this->once())
            ->method('addMethodCall')
            ->with('configure');
        $this->presetConfigDef->expects($this->once())
            ->method('addMethodCall')
            ->with('configure');

        $this->compilerPass->process($this->container);
    }

    public function testProcessWithClassWithAccessAttribute(): void
    {
        $definition = $this->createMock(Definition::class);
        $definition->method('getClass')
            ->willReturn(AccessAnnotatedController::class);

        $this->container->expects($this->once())
            ->method('getDefinitions')
            ->willReturn(['test_service' => $definition]);

        $this->container->expects($this->exactly(3))
            ->method('findDefinition')
            ->willReturnCallback(function ($id) {
                return match ($id) {
                    AccessRoleStore::class => $this->roleStoreDef,
                    AccessGroupConfig::class => $this->groupConfigDef,
                    AccessPresetConfig::class => $this->presetConfigDef,
                    default => throw new \InvalidArgumentException("Unexpected service ID: $id"),
                };
            });

        $this->container->expects($this->once())
            ->method('getReflectionClass')
            ->with(AccessAnnotatedController::class, false)
            ->willReturn(new \ReflectionClass(AccessAnnotatedController::class));

        $expectedRoleName = AccessRoleFormatter::from(AccessAnnotatedController::class);
        $expectedArgs = [
            $expectedRoleName,
            'Controller Access',
            null,
            null,
            []
        ];

        // Count calls to ensure both configure and addRole are called
        $configureCallCount = 0;
        $addRoleCallCount = 0;
        
        $this->roleStoreDef->expects($this->exactly(2))
            ->method('addMethodCall')
            ->willReturnCallback(function ($method, $args = null) use ($expectedArgs, &$configureCallCount, &$addRoleCallCount) {
                if ($method === 'configure') {
                    $configureCallCount++;
                } elseif ($method === 'addRole' && $args === $expectedArgs) {
                    $addRoleCallCount++;
                }
                return $this->roleStoreDef;
            });
            
        // Other services just get configure
        $this->groupConfigDef->expects($this->once())
            ->method('addMethodCall')
            ->with('configure');
        $this->presetConfigDef->expects($this->once())
            ->method('addMethodCall')
            ->with('configure');

        $this->compilerPass->process($this->container);

        // Verify both calls happened
        $this->assertEquals(1, $configureCallCount, "Configure method should be called once");
        $this->assertEquals(1, $addRoleCallCount, "AddRole method should be called once");
    }

    public function testProcessWithMethodWithAccessAttribute(): void
    {
        $definition = $this->createMock(Definition::class);
        $definition->method('getClass')
            ->willReturn(ControllerWithAnnotatedMethod::class);

        $this->container->expects($this->once())
            ->method('getDefinitions')
            ->willReturn(['test_service' => $definition]);

        $this->container->expects($this->exactly(3))
            ->method('findDefinition')
            ->willReturnCallback(function ($id) {
                return match ($id) {
                    AccessRoleStore::class => $this->roleStoreDef,
                    AccessGroupConfig::class => $this->groupConfigDef,
                    AccessPresetConfig::class => $this->presetConfigDef,
                    default => throw new \InvalidArgumentException("Unexpected service ID: $id"),
                };
            });

        $this->container->expects($this->once())
            ->method('getReflectionClass')
            ->with(ControllerWithAnnotatedMethod::class, false)
            ->willReturn(new \ReflectionClass(ControllerWithAnnotatedMethod::class));

        $expectedRoleName = AccessRoleFormatter::from(ControllerWithAnnotatedMethod::class, 'annotatedMethod');
        $expectedArgs = [
            $expectedRoleName,
            'Method Access',
            null,
            null,
            []
        ];

        // Count calls to ensure both configure and addRole are called
        $configureCallCount = 0;
        $addRoleCallCount = 0;
        
        $this->roleStoreDef->expects($this->exactly(2))
            ->method('addMethodCall')
            ->willReturnCallback(function ($method, $args = null) use ($expectedArgs, &$configureCallCount, &$addRoleCallCount) {
                if ($method === 'configure') {
                    $configureCallCount++;
                } elseif ($method === 'addRole' && $args === $expectedArgs) {
                    $addRoleCallCount++;
                }
                return $this->roleStoreDef;
            });
            
        // Other services just get configure
        $this->groupConfigDef->expects($this->once())
            ->method('addMethodCall')
            ->with('configure');
        $this->presetConfigDef->expects($this->once())
            ->method('addMethodCall')
            ->with('configure');

        $this->compilerPass->process($this->container);

        // Verify both calls happened
        $this->assertEquals(1, $configureCallCount, "Configure method should be called once");
        $this->assertEquals(1, $addRoleCallCount, "AddRole method should be called once");
    }
}

/**
 * Test classes - should be moved to separate files in real implementation
 */
#[Access(title: "Controller Access")]
class AccessAnnotatedController
{
    public function index(): void {}
}

class ControllerWithAnnotatedMethod
{
    #[Access(title: "Method Access")]
    public function annotatedMethod(): void {}

    public function nonAnnotatedMethod(): void {}
}

class TestController
{
    public function index(): void {}
}