<?php

namespace Ema\AccessBundle\Role;

use Doctrine\DBAL\ArrayParameterType;
use Doctrine\DBAL\ParameterType;
use Doctrine\ORM\EntityManagerInterface;
use Doctrine\Persistence\ManagerRegistry;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Dto\AccessRoleDto;
use Ema\AccessBundle\Entity\AccessRole;

abstract class AbstractAccessRoleStore implements AccessRoleStore
{
    protected array $roles = [];

    public function __construct(
        private readonly ManagerRegistry $managerRegistry,
    ) {
    }

    public function configure(): void
    {

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
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass();
        $entity->setName($role->getName())
            ->setTitle($role->getTitle())
            ->setOptions($role->options)
        ;
        return $entity;
    }

    public function createQueryBuilder(string $alias = 'entity'): \Doctrine\ORM\QueryBuilder
    {
        $em = $this->managerRegistry->getManagerForClass($this->getEntityClass());
        return $em->getRepository($this->getEntityClass())->createQueryBuilder($alias);
    }

    public function persistRole(AccessRoleDto $role): void
    {
        $em = $this->managerRegistry->getManagerForClass($this->getEntityClass());
        $entity = $this->createEntity($role);
        $em->persist($entity);
        $em->flush();
    }

    public function findBy(array $params): array
    {
        $em = $this->managerRegistry->getManagerForClass($this->getEntityClass());
        return $em->getRepository($this->getEntityClass())->findBy($params);
    }
    
    public function clearCache(): void
    {
        // No-op in base implementation
    }
}