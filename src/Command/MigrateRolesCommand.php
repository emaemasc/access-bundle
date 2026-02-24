<?php

namespace Ema\AccessBundle\Command;

use Ema\AccessBundle\Contracts\AccessRoleStore;
use Symfony\Component\Console\Attribute\AsCommand;
use Symfony\Component\Console\Command\Command;
use Symfony\Component\Console\Input\InputInterface;
use Symfony\Component\Console\Output\OutputInterface;

#[AsCommand('emaemasc:access:migrate', 'Flush roles to database')]
class MigrateRolesCommand extends Command
{
    public function __construct(
        private readonly AccessRoleStore $roleStore,
    ) {
        parent::__construct();
    }

    protected function execute(InputInterface $input, OutputInterface $output): int
    {
        $names = \array_keys($this->roleStore->getRoles());
        $qb = $this->roleStore->createQueryBuilder();
        $qb->delete()
            ->where($qb->expr()->notIn('entity.name', ':names'))
            ->setParameter('names', $names)
            ->getQuery()
            ->getResult();

        $state = $this->roleStore
            ->createQueryBuilder()
            ->select('entity.name')
            ->getQuery()
            ->getSingleColumnResult();
        $newNames = \array_diff($names, $state);
        foreach ($newNames as $name) {
            $role = $this->roleStore->getRole($name);
            $this->roleStore->persistRole($role);
        }

        return Command::SUCCESS;
    }
}
