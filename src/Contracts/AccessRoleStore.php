<?php

namespace Ema\AccessBundle\Contracts;

use Doctrine\ORM\EntityManagerInterface;
use Doctrine\ORM\QueryBuilder;
use Ema\AccessBundle\Dto\AccessRoleDto;
use Ema\AccessBundle\Entity\AccessRole;
use Symfony\Component\DependencyInjection\Attribute\AutoconfigureTag;

#[AutoconfigureTag]
interface AccessRoleStore
{
    public function getPrefix(): string;
    public function getEntityClass(): string;

    public function getRoleHierarchy(): array;
    /**
     * @return AccessRoleDto[]
     */
    public function getRoles(): array;
    public function getRoleNames(): array;
    public function getRole(string $name): ?AccessRoleDto;

    public function getEntityManager(): EntityManagerInterface;
    public function createQueryBuilder(string $alias = 'entity'): QueryBuilder;
    public function createEntity(AccessRoleDto $role): AccessRole;
    public function syncEntity(AccessRoleDto $role, AccessRole $entity): AccessRole;

    public function findBy(array $params): array;
    public function persistRole(AccessRoleDto $role): void;
}
