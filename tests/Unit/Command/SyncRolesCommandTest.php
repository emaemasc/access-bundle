<?php

namespace Ema\AccessBundle\Tests\Unit\Command;

use Doctrine\ORM\EntityManagerInterface;
use Ema\AccessBundle\Command\SyncRolesCommand;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Dto\AccessRoleDto;
use Ema\AccessBundle\Entity\AccessRole;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\MockObject\MockObject;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AllowMockObjectsWithoutExpectations]
class SyncRolesCommandTest extends TestCase
{
    private MockObject|AccessRoleStore $roleStore;
    private MockObject|EntityManagerInterface $entityManager;
    private SyncRolesCommand $command;

    protected function setUp(): void
    {
        $this->roleStore = $this->createMock(AccessRoleStore::class);
        $this->entityManager = $this->createMock(EntityManagerInterface::class);
        $this->command = new SyncRolesCommand($this->roleStore);
    }

    public function testExecuteUpdatesExistingRolesCreatesMissingOnesAndRemovesObsoleteOnes(): void
    {
        $existingRole = new AccessRole();
        $existingRole->setName('EAB_EXISTING')->setTitle('Old title');

        $obsoleteRole = new AccessRole();
        $obsoleteRole->setName('EAB_OLD')->setTitle('Old role');

        $updatedRole = new AccessRoleDto('EAB_EXISTING', 'New title', props: ['scope' => 'admin'], group: null, presets: []);
        $newRole = new AccessRoleDto('EAB_NEW', 'Brand new', props: ['scope' => 'editor'], group: null, presets: []);
        $newEntity = new AccessRole();

        $this->roleStore->expects($this->once())
            ->method('getRoleNames')
            ->willReturn(['EAB_EXISTING', 'EAB_NEW']);

        $this->roleStore->expects($this->once())
            ->method('findBy')
            ->willReturnCallback(function (array $params) use ($existingRole, $obsoleteRole): array {
                self::assertSame([], $params);

                return [$existingRole, $obsoleteRole];
            });

        $this->roleStore->expects($this->exactly(2))
            ->method('getRole')
            ->willReturnCallback(function (string $name) use ($updatedRole, $newRole): ?AccessRoleDto {
                return match ($name) {
                    'EAB_EXISTING' => $updatedRole,
                    'EAB_NEW' => $newRole,
                    default => null,
                };
            });

        $this->roleStore->expects($this->once())
            ->method('syncEntity')
            ->with($updatedRole, $existingRole)
            ->willReturnCallback(static function (AccessRoleDto $role, AccessRole $entity): AccessRole {
                $entity->setName($role->getName());
                $entity->setTitle($role->getTitle());

                return $entity;
            });

        $this->roleStore->expects($this->once())
            ->method('createEntity')
            ->with($newRole)
            ->willReturn($newEntity);

        $this->roleStore->expects($this->once())
            ->method('getEntityManager')
            ->willReturn($this->entityManager);

        $this->entityManager->expects($this->once())
            ->method('remove')
            ->with($obsoleteRole);

        $this->entityManager->expects($this->once())
            ->method('persist')
            ->with($newEntity);

        $this->entityManager->expects($this->once())
            ->method('flush');

        $input = $this->createMock(InputInterface::class);
        $output = $this->createMock(OutputInterface::class);

        self::assertSame(0, $this->command->run($input, $output));
        self::assertSame('New title', $existingRole->getTitle());
    }
}
