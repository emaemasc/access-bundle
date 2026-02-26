<?php

namespace Ema\AccessBundle\Role;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Dto\AccessRoleDto;
use Ema\AccessBundle\Entity\AccessRole;
use Psr\Cache\CacheItemPoolInterface;

/**
 * Decorator that adds caching capabilities to an existing AccessRoleStore
 */
class CachedAccessRoleStore implements AccessRoleStore
{
    private string $cacheKeyPrefix = 'ema.access_bundle.';
    private int $defaultTtl = 3600; // 1 hour
    
    // Internal cache to avoid repeated cache lookups during the same request
    private ?array $cachedRoles = null;

    public function __construct(
        private readonly AccessRoleStore         $decoratedStore,
        private readonly ?CacheItemPoolInterface $cachePool = null
    ) {
    }

    public function getRoles(): array
    {
        if ($this->cachedRoles !== null) {
            return $this->cachedRoles;
        }
        
        if (!$this->cachePool) {
            return $this->cachedRoles = $this->decoratedStore->getRoles();
        }
        
        $cacheKey = $this->cacheKeyPrefix . 'roles';
        $cacheItem = $this->cachePool->getItem($cacheKey);
        
        if ($cacheItem->isHit()) {
            return $this->cachedRoles = $cacheItem->get();
        }
        
        $roles = $this->decoratedStore->getRoles();
        $this->cachePool->save($cacheItem->set($roles)->expiresAfter($this->defaultTtl));
        
        return $this->cachedRoles = $roles;
    }

    public function clearCache(): void
    {
        $this->cachePool?->deleteItem($this->cacheKeyPrefix . 'roles');
        $this->cachedRoles = null;
    }

    public function setCachePrefix(string $prefix): self
    {
        $this->cacheKeyPrefix = $prefix;
        return $this;
    }

    public function setDefaultTtl(int $ttl): self
    {
        $this->defaultTtl = $ttl;
        return $this;
    }
    
    public function getCachePool(): ?CacheItemPoolInterface
    {
        return $this->cachePool;
    }
    
    public function getDecoratedStore(): AccessRoleStore
    {
        return $this->decoratedStore;
    }

    public function getEntityClass(): string
    {
        return $this->decoratedStore->getEntityClass();
    }

    public function getPrefix(): string
    {
        return $this->decoratedStore->getPrefix();
    }

    public function getRoleHierarchy(): array
    {
        return $this->decoratedStore->getRoleHierarchy();
    }

    public function getRoleNames(): array
    {
        return $this->decoratedStore->getRoleNames();
    }

    public function getRole(string $name): ?AccessRoleDto
    {
        return $this->decoratedStore->getRole($name);
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->decoratedStore->getEntityManager();
    }

    public function createQueryBuilder(string $alias = 'entity'): QueryBuilder
    {
        return $this->decoratedStore->createQueryBuilder($alias);
    }

    public function createEntity(AccessRoleDto $role): AccessRole
    {
        return $this->decoratedStore->createEntity($role);
    }

    public function persistRole(AccessRoleDto $role): void
    {
        $this->decoratedStore->persistRole($role);
    }

    public function findBy(array $params): array
    {
        return $this->decoratedStore->findBy($params);
    }
}