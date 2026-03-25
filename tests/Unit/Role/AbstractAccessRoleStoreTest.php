<?php

namespace Ema\AccessBundle\Tests\Unit\Role;

use Doctrine\Persistence\ManagerRegistry;
use Ema\AccessBundle\Dto\AccessRoleDto;
use Ema\AccessBundle\Entity\AccessRole;
use Ema\AccessBundle\Role\AbstractAccessRoleStore;
use PHPUnit\Framework\TestCase;

class AbstractAccessRoleStoreTest extends TestCase
{
    public function testCreateEntityMapsCustomFieldsViaPropertyAccessor(): void
    {
        $store = new TestAccessRoleStore($this->createMock(ManagerRegistry::class));
        $role = new AccessRoleDto(
            'EAB_TEST_ROLE',
            'Test role',
            props: ['scope' => 'admin'],
            group: null,
            presets: []
        );

        $entity = $store->createEntity($role);

        self::assertInstanceOf(TestAccessRole::class, $entity);
        self::assertSame('EAB_TEST_ROLE', $entity->getName());
        self::assertSame('Test role', $entity->getTitle());
        self::assertSame('admin', $entity->getScope());
    }

    public function testSyncEntityMapsCustomFieldsOntoExistingEntity(): void
    {
        $store = new TestAccessRoleStore($this->createMock(ManagerRegistry::class));
        $role = new AccessRoleDto(
            'EAB_TEST_ROLE',
            'Updated title',
            props: ['scope' => 'editor'],
            group: null,
            presets: []
        );
        $entity = new TestAccessRole();

        $entity = $store->syncEntity($role, $entity);

        self::assertInstanceOf(TestAccessRole::class, $entity);
        self::assertSame('EAB_TEST_ROLE', $entity->getName());
        self::assertSame('Updated title', $entity->getTitle());
        self::assertSame('editor', $entity->getScope());
    }
}

class TestAccessRoleStore extends AbstractAccessRoleStore
{
    public function getEntityClass(): string
    {
        return TestAccessRole::class;
    }
}

class TestAccessRole extends AccessRole
{
    protected ?string $scope = null;

    public function getScope(): ?string
    {
        return $this->scope;
    }

    public function setScope(?string $scope): self
    {
        $this->scope = $scope;

        return $this;
    }
}
