<?php

namespace Ema\AccessBundle\Tests\Unit\DependencyInjection;

use Ema\AccessBundle\DependencyInjection\AccessCompilerPass;
use Ema\AccessBundle\Attribute\Access;
use Ema\AccessBundle\Attribute\AccessGroup;
use Ema\AccessBundle\Attribute\AccessPreset;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Role\AccessRoleFormatter;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;

class AccessCompilerPassTest extends TestCase
{
    private ContainerBuilder|MockObject $container;
    private AccessCompilerPass $compilerPass;
    private Definition|MockObject $roleStoreDef;

    protected function setUp(): void
    {
        $this->container = $this->createMock(ContainerBuilder::class);
        $this->roleStoreDef = $this->createMock(Definition::class);
        $this->compilerPass = new AccessCompilerPass();
    }

    public function testProcessWithNoDefinitions(): void
    {
        $this->container->expects($this->once())
            ->method('getDefinitions')
            ->willReturn([]);

        $this->container->expects($this->once())
            ->method('findDefinition')
            ->with(AccessRoleStore::class)
            ->willReturn($this->roleStoreDef);

        $this->compilerPass->process($this->container);
    }

    public function testProcessWithClassWithoutAccessAttribute(): void
    {
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->any())
            ->method('getClass')
            ->willReturn(TestController::class);

        $this->container->expects($this->once())
            ->method('getDefinitions')
            ->willReturn(['test_service' => $definition]);

        $this->container->expects($this->once())
            ->method('findDefinition')
            ->with(AccessRoleStore::class)
            ->willReturn($this->roleStoreDef);

        $this->container->expects($this->once())
            ->method('getReflectionClass')
            ->with(TestController::class, false)
            ->willReturn(new \ReflectionClass(TestController::class));

        $this->roleStoreDef->expects($this->never())
            ->method('addMethodCall');

        $this->compilerPass->process($this->container);
    }

    public function testProcessWithClassWithAccessAttribute(): void
    {
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->any())
            ->method('getClass')
            ->willReturn(AccessAnnotatedController::class);

        $this->container->expects($this->once())
            ->method('getDefinitions')
            ->willReturn(['test_service' => $definition]);

        $this->container->expects($this->once())
            ->method('findDefinition')
            ->with(AccessRoleStore::class)
            ->willReturn($this->roleStoreDef);

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

        $this->roleStoreDef->expects($this->once())
            ->method('addMethodCall')
            ->with('addRole', $expectedArgs);

        $this->compilerPass->process($this->container);
    }

    public function testProcessWithMethodWithAccessAttribute(): void
    {
        $definition = $this->createMock(Definition::class);
        $definition->expects($this->any())
            ->method('getClass')
            ->willReturn(ControllerWithAnnotatedMethod::class);

        $this->container->expects($this->once())
            ->method('getDefinitions')
            ->willReturn(['test_service' => $definition]);

        $this->container->expects($this->once())
            ->method('findDefinition')
            ->with(AccessRoleStore::class)
            ->willReturn($this->roleStoreDef);

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

        $this->roleStoreDef->expects($this->once())
            ->method('addMethodCall')
            ->with('addRole', $expectedArgs);

        $this->compilerPass->process($this->container);
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