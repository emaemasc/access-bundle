<?php

namespace Ema\AccessBundle\Tests\Unit\Role;

use Ema\AccessBundle\Role\CachedAccessRoleStore;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Dto\AccessRoleDto;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Psr\Cache\CacheItemInterface;
use Psr\Cache\CacheItemPoolInterface;

#[AllowMockObjectsWithoutExpectations]
class CachedAccessRoleStoreTest extends TestCase
{
    private AccessRoleStore|MockObject $decoratedStore;
    private CacheItemPoolInterface|MockObject $cachePool;
    private CachedAccessRoleStore $cachedStore;

    protected function setUp(): void
    {
        $this->decoratedStore = $this->createMock(AccessRoleStore::class);
        $this->cachePool = $this->createMock(CacheItemPoolInterface::class);
        $this->cachedStore = new CachedAccessRoleStore($this->decoratedStore, $this->cachePool);
    }

    public function testGetEntityClassDelegatesToDecoratedStore(): void
    {
        $this->decoratedStore->expects($this->once())
            ->method('getEntityClass')
            ->willReturn('Test\Entity\Class');

        $result = $this->cachedStore->getEntityClass();

        $this->assertEquals('Test\Entity\Class', $result);
    }

    public function testGetRolesReturnsFromCacheWhenAvailable(): void
    {
        $expectedRoles = [
            'role1' => new AccessRoleDto('role1', 'Role 1', null, null, []),
            'role2' => new AccessRoleDto('role2', 'Role 2', null, null, [])
        ];

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(true);
        $cacheItem->expects($this->once())
            ->method('get')
            ->willReturn($expectedRoles);

        $this->cachePool->expects($this->once())
            ->method('getItem')
            ->with('ema.access_bundle.roles')
            ->willReturn($cacheItem);

        $this->decoratedStore->expects($this->never())
            ->method('getRoles'); // Should not be called if cache hit

        $result = $this->cachedStore->getRoles();

        $this->assertSame($expectedRoles, $result);
    }

    public function testGetRolesFetchesFromStoreAndCachesWhenNotInCache(): void
    {
        $expectedRoles = [
            'role1' => new AccessRoleDto('role1', 'Role 1', null, null, []),
            'role2' => new AccessRoleDto('role2', 'Role 2', null, null, [])
        ];

        $cacheItem = $this->createMock(CacheItemInterface::class);
        $cacheItem->expects($this->once())
            ->method('isHit')
            ->willReturn(false);
        $cacheItem->expects($this->once())
            ->method('set')
            ->with($expectedRoles)
            ->willReturnSelf();
        $cacheItem->expects($this->once())
            ->method('expiresAfter')
            ->with(3600)
            ->willReturnSelf();

        $this->cachePool->expects($this->once())
            ->method('getItem')
            ->with('ema.access_bundle.roles')
            ->willReturn($cacheItem);
        $this->cachePool->expects($this->once())
            ->method('save')
            ->with($cacheItem);

        $this->decoratedStore->expects($this->once())
            ->method('getRoles')
            ->willReturn($expectedRoles);

        $result = $this->cachedStore->getRoles();

        $this->assertSame($expectedRoles, $result);
    }

    public function testGetRolesWithoutCachePoolReturnsDirectlyFromStore(): void
    {
        $cachedStore = new CachedAccessRoleStore($this->decoratedStore, null);
        
        $expectedRoles = [
            'role1' => new AccessRoleDto('role1', 'Role 1', null, null, [])
        ];

        $this->decoratedStore->expects($this->once())
            ->method('getRoles')
            ->willReturn($expectedRoles);

        $result = $cachedStore->getRoles();

        $this->assertSame($expectedRoles, $result);
    }

    public function testFindByDelegatesToDecoratedStore(): void
    {
        $params = ['name' => 'test'];
        $expectedResults = [];

        $this->decoratedStore->expects($this->once())
            ->method('findBy')
            ->with($params)
            ->willReturn($expectedResults);

        $result = $this->cachedStore->findBy($params);

        $this->assertSame($expectedResults, $result);
    }

    public function testClearCacheRemovesCacheItems(): void
    {
        $this->cachePool->expects($this->exactly(1))
            ->method('deleteItem')
            ->with('ema.access_bundle.roles');

        $this->cachedStore->clearCache();
    }

    public function testSettersReturnSelf(): void
    {
        $this->assertInstanceOf(CachedAccessRoleStore::class, $this->cachedStore->setCachePrefix('test.'));
        $this->assertInstanceOf(CachedAccessRoleStore::class, $this->cachedStore->setDefaultTtl(7200));
    }
    
    public function testGetPrefixDelegatesToDecoratedStore(): void
    {
        $this->decoratedStore->expects($this->once())
            ->method('getPrefix')
            ->willReturn('TEST_');

        $result = $this->cachedStore->getPrefix();

        $this->assertEquals('TEST_', $result);
    }

    public function testGetRoleHierarchyDelegatesToDecoratedStore(): void
    {
        $expectedHierarchy = ['ROLE_SUPER_ADMIN' => '/.*/'];
        
        $this->decoratedStore->expects($this->once())
            ->method('getRoleHierarchy')
            ->willReturn($expectedHierarchy);

        $result = $this->cachedStore->getRoleHierarchy();

        $this->assertSame($expectedHierarchy, $result);
    }

    public function testGetRoleNamesDelegatesToDecoratedStore(): void
    {
        $expectedNames = ['ROLE_TEST_1', 'ROLE_TEST_2'];
        
        $this->decoratedStore->expects($this->once())
            ->method('getRoleNames')
            ->willReturn($expectedNames);

        $result = $this->cachedStore->getRoleNames();

        $this->assertSame($expectedNames, $result);
    }
}