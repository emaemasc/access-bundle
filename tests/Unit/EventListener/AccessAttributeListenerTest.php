<?php

namespace Ema\AccessBundle\Tests\Unit\EventListener;

use Ema\AccessBundle\EventListener\AccessAttributeListener;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Attribute\Access;
use Ema\AccessBundle\Role\AccessRoleFormatter;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\ExpressionLanguage\Expression;
use Symfony\Component\ExpressionLanguage\ExpressionLanguage;

#[AllowMockObjectsWithoutExpectations]
class AccessAttributeListenerTest extends TestCase
{
    private MockObject|AuthorizationCheckerInterface $authChecker;
    private AccessAttributeListener $listener;

    protected function setUp(): void
    {
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->listener = new AccessAttributeListener($this->authChecker);
    }

    public function testInvokeWithNoControllerArray(): void
    {
        $request = new Request();
        $controller = static function() {}; // Use a proper callable instead of string
        $kernel = $this->createMock(HttpKernelInterface::class);
        
        $event = new ControllerArgumentsEvent($kernel, $controller, [], $request, HttpKernelInterface::MAIN_REQUEST);
        
        // This should return early without doing anything
        $this->listener->__invoke($event);
        
        $this->expectNotToPerformAssertions(); // Just ensure no exception is thrown
    }

    public function testInvokeWithNoAccessAttributes(): void
    {
        $request = new Request();
        $controllerObject = new class() {
            public function testAction(): void {}
        };
        $controller = [$controllerObject, 'testAction'];
        $kernel = $this->createMock(HttpKernelInterface::class);
        
        $event = new ControllerArgumentsEvent($kernel, $controller, [], $request, HttpKernelInterface::MAIN_REQUEST);
        
        // This should return early without doing anything
        $this->listener->__invoke($event);
        
        $this->expectNotToPerformAssertions();
    }

    public function testInvokeWithSuperRole(): void
    {
        // In the new implementation, super roles are handled by the voter,
        // so the listener doesn't check for super roles anymore.
        // But we still need to mock the auth checker to grant access
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->willReturn(true);

        $request = new Request();
        $controllerObject = new class() {
            #[Access(title: "Test Action")]
            public function testAction(): void {}
        };
        $controller = [$controllerObject, 'testAction'];
        $kernel = $this->createMock(HttpKernelInterface::class);
        
        $event = new ControllerArgumentsEvent($kernel, $controller, [], $request, HttpKernelInterface::MAIN_REQUEST);
        
        // Should not throw an exception
        $this->listener->__invoke($event);
    }

    public function testInvokeWithGrantedPermission(): void
    {
        $controllerObject = new class() {
            #[Access(title: "Test Action")]
            public function testAction(): void {}
        };
        
        $roleName = AccessRoleFormatter::from(get_class($controllerObject), 'testAction');
        
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with($roleName, null)
            ->willReturn(true);

        $request = new Request();
        $controller = [$controllerObject, 'testAction'];
        $kernel = $this->createMock(HttpKernelInterface::class);
        
        $event = new ControllerArgumentsEvent($kernel, $controller, [], $request, HttpKernelInterface::MAIN_REQUEST);
        
        $this->listener->__invoke($event);
    }

    public function testInvokeWithDeniedPermissionThrowsException(): void
    {
        $controllerObject = new class() {
            #[Access(title: "Test Action")]
            public function testAction(): void {}
        };
        
        $roleName = AccessRoleFormatter::from(get_class($controllerObject), 'testAction');
        
        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with($roleName, null)
            ->willReturn(false);

        $request = new Request();
        $controller = [$controllerObject, 'testAction'];
        $kernel = $this->createMock(HttpKernelInterface::class);
        
        $event = new ControllerArgumentsEvent($kernel, $controller, [], $request, HttpKernelInterface::MAIN_REQUEST);
        
        $this->expectException(AccessDeniedException::class);
        
        $this->listener->__invoke($event);
    }
}