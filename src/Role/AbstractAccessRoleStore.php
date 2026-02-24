<?php

namespace Ema\AccessBundle\Role;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Dto\AccessRoleDto;

abstract class AbstractAccessRoleStore implements AccessRoleStore
{
    protected EntityManagerInterface $entityManager;
    protected string $entityClass;
    protected array $superRoles = [];
    protected array $roles = [];

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->entityManager;
    }

    public function setEntityManager(EntityManagerInterface $entityManager): self
    {
        $this->entityManager = $entityManager;
        return $this;
    }

    public function getEntityClass(): string
    {
        return $this->entityClass;
    }

    public function setEntityClass(string $entityClass): self
    {
        $this->entityClass = $entityClass;
        return $this;
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getRoleNames(): array
    {
        return \array_keys($this->roles);
    }

    public function getRole(string $name): ?AccessRoleDto
    {
        return $this->roles[$name] ?? null;
    }

    public function addRole(string $name, string $title, ?array $options = null, ?string $group = null, array $presets = []): void
    {
        $this->roles[$name] = new AccessRoleDto($name, $title, $options, $group, $presets);
    }

    public function createEntity(AccessRoleDto $role): object
    {
        $entity = new $this->entityClass();
        $entity->setName($role->getName())
            ->setTitle($role->getTitle())
            ->setOptions($role->options)
        ;
        return $entity;
    }

    public function createQueryBuilder(string $alias = 'entity'): \Doctrine\ORM\QueryBuilder
    {
        return $this->entityManager
            ->getRepository($this->entityClass)
            ->createQueryBuilder($alias);
    }

    public function persistRole(AccessRoleDto $role): void
    {
        $entity = $this->createEntity($role);
        $this->entityManager->persist($entity);
        $this->entityManager->flush();
    }

    public function getSuperRoles(): array
    {
        return $this->superRoles;
    }

    public function setSuperRoles(array $superRoles): self
    {
        $this->superRoles = $superRoles;
        return $this;
    }

    public function findBy(array $params): array
    {
        return $this->entityManager->getRepository($this->entityClass)->findBy($params);
    }
}
