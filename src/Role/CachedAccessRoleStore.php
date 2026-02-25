<?php

namespace Ema\AccessBundle\Role;

use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Dto\AccessRoleDto;
use Psr\Cache\CacheItemPoolInterface;
use Psr\Cache\CacheItemInterface;

/**
 * Decorator that adds caching capabilities to an existing AccessRoleStore
 */
class CachedAccessRoleStore implements AccessRoleStore
{
    private string $cacheKeyPrefix = 'ema.access_bundle.';
    private int $defaultTtl = 3600; // 1 hour
    
    // Internal cache to avoid repeated cache lookups during the same request
    private ?array $cachedRoles = null;
    private ?array $cachedSuperRoles = null;

    public function __construct(
        private readonly AccessRoleStore         $decoratedStore,
        private readonly ?CacheItemPoolInterface $cachePool = null
    ) {
    }

    public function getEntityClass(): string
    {
        return $this->decoratedStore->getEntityClass();
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

    public function getSuperRoles(): array
    {
        if ($this->cachedSuperRoles !== null) {
            return $this->cachedSuperRoles;
        }
        
        if (!$this->cachePool) {
            return $this->cachedSuperRoles = $this->decoratedStore->getSuperRoles();
        }
        
        $cacheKey = $this->cacheKeyPrefix . 'super_roles';
        $cacheItem = $this->cachePool->getItem($cacheKey);
        
        if ($cacheItem->isHit()) {
            return $this->cachedSuperRoles = $cacheItem->get();
        }
        
        $superRoles = $this->decoratedStore->getSuperRoles();
        $this->cachePool->save($cacheItem->set($superRoles)->expiresAfter($this->defaultTtl));
        
        return $this->cachedSuperRoles = $superRoles;
    }

    public function findBy(array $params): array
    {
        return $this->decoratedStore->findBy($params);
    }

    public function clearCache(): void
    {
        if ($this->cachePool) {
            // Clear all related cache entries
            $this->cachePool->deleteItem($this->cacheKeyPrefix . 'roles');
            $this->cachePool->deleteItem($this->cacheKeyPrefix . 'super_roles');
        }
        
        // Reset internal cache
        $this->cachedRoles = null;
        $this->cachedSuperRoles = null;
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
}