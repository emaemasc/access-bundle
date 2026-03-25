<?php

namespace Ema\AccessBundle\Command;

use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Entity\AccessRole;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('emaemasc:access:sync', 'Sync roles with database')]
class SyncRolesCommand extends Command
{
    public function __construct(
        private readonly AccessRoleStore $roleStore,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $names = $this->roleStore->getRoleNames();

        $existingRoles = $this->roleStore->findBy([]);
        $existingByName = [];
        $nameSet = \array_flip($names);

        foreach ($existingRoles as $existingRole) {
            if ($existingRole instanceof AccessRole) {
                $existingByName[$existingRole->getName()] = $existingRole;
            }
        }

        $entityManager = $this->roleStore->getEntityManager();

        foreach ($existingRoles as $existingRole) {
            if ($existingRole instanceof AccessRole && !isset($nameSet[$existingRole->getName()])) {
                $entityManager->remove($existingRole);
            }
        }

        foreach ($names as $name) {
            $role = $this->roleStore->getRole($name);
            if (null === $role) {
                continue;
            }

            if (isset($existingByName[$name])) {
                $this->roleStore->syncEntity($role, $existingByName[$name]);
                continue;
            }

            $entityManager->persist($this->roleStore->createEntity($role));
        }

        $entityManager->flush();

        return Command::SUCCESS;
    }
}
