<?php

namespace Ema\AccessBundle\Role;

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

    public function getPrefix(): string
    {
        return 'EAB_';
    }

    public function getRoleHierarchy(): array
    {
        return [
            'ROLE_SUPER_ADMIN' => "/{$this->getPrefix()}.*/",
        ];
    }

    public function getRoles(): array
    {
        return $this->roles;
    }

    public function getRoleNames(): array
    {
        return \array_keys($this->getRoles());
    }

    public function getRole(string $name): ?AccessRoleDto
    {
        return $this->getRoles()[$name] ?? null;
    }

    public function addRole(string $name, string $title, ?array $options = null, ?string $group = null, array $presets = []): void
    {
        $this->roles[$name] = new AccessRoleDto($name, $title, $options, $group, $presets);
    }

    public function createEntity(AccessRoleDto $role): AccessRole
    {
        $entityClass = $this->getEntityClass();
        $entity = new $entityClass();
        $entity->setName($role->getName())
            ->setTitle($role->getTitle())
            ->setOptions($role->options)
        ;
        return $entity;
    }

    public function getEntityManager(): EntityManagerInterface
    {
        return $this->managerRegistry->getManagerForClass($this->getEntityClass());
    }

    public function createQueryBuilder(string $alias = 'entity'): \Doctrine\ORM\QueryBuilder
    {
        return $this->getEntityManager()
            ->getRepository($this->getEntityClass())
            ->createQueryBuilder($alias);
    }

    public function persistRole(AccessRoleDto $role): void
    {
        $entity = $this->createEntity($role);
        $this->getEntityManager()->persist($entity);
        $this->getEntityManager()->flush();
    }

    public function findBy(array $params): array
    {
        return $this->getEntityManager()
            ->getRepository($this->getEntityClass())
            ->findBy($params);
    }

    public function clearCache(): void
    {
        // No-op in base implementation
    }
}
