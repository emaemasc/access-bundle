<?php

namespace Ema\AccessBundle\Tests\Unit\EventListener;

use Ema\AccessBundle\EventListener\AccessAttributeListener;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Attribute\Access;
use Ema\AccessBundle\Role\AccessRoleFormatter;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\HttpFoundation\Request;
use Symfony\Component\HttpKernel\Event\ControllerArgumentsEvent;
use Symfony\Component\HttpKernel\HttpKernelInterface;
use Symfony\Component\Security\Core\Authorization\AuthorizationCheckerInterface;
use Symfony\Component\Security\Core\Exception\AccessDeniedException;
use Symfony\Component\ExpressionLanguage\Expression;

class AccessAttributeListenerTest extends TestCase
{
    private MockObject|AuthorizationCheckerInterface $authChecker;
    private MockObject|AccessRoleStore $roleStore;
    private AccessAttributeListener $listener;

    protected function setUp(): void
    {
        $this->authChecker = $this->createMock(AuthorizationCheckerInterface::class);
        $this->roleStore = $this->createMock(AccessRoleStore::class);
        $this->listener = new AccessAttributeListener($this->authChecker, $this->roleStore);
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
        $this->roleStore->expects($this->once())
            ->method('getSuperRoles')
            ->willReturn(['ROLE_SUPER_ADMIN']);

        $this->authChecker->expects($this->once())
            ->method('isGranted')
            ->with('ROLE_SUPER_ADMIN')
            ->willReturn(true);

        $request = new Request();
        $controllerObject = new class() {
            #[Access(title: "Test Action")]
            public function testAction(): void {}
        };
        $controller = [$controllerObject, 'testAction'];
        $kernel = $this->createMock(HttpKernelInterface::class);
        
        $event = new ControllerArgumentsEvent($kernel, $controller, [], $request, HttpKernelInterface::MAIN_REQUEST);
        
        // Should return early due to super role
        $this->listener->__invoke($event);
        
        $this->expectNotToPerformAssertions();
    }

    public function testInvokeWithGrantedPermission(): void
    {
        $this->roleStore->expects($this->once())
            ->method('getSuperRoles')
            ->willReturn([]);

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
        
        $this->expectNotToPerformAssertions();
    }

    public function testInvokeWithDeniedPermissionThrowsException(): void
    {
        $this->roleStore->expects($this->once())
            ->method('getSuperRoles')
            ->willReturn([]);

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