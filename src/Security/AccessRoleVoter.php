<?php

namespace Ema\AccessBundle\Security;

use Ema\AccessBundle\Contracts\AccessRoleStore;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;
use Symfony\Component\Security\Core\Role\RoleHierarchy;

class AccessRoleVoter implements CacheableVoterInterface
{
    private RoleHierarchy $roleHierarchy;
    private array $reachableRoles = [];

    public function __construct(
        private readonly AccessRoleStore $roleStore,
    ) {
        $this->roleHierarchy = $this->buildHierarchy();
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        $roles = $this->extractRoles($token);
        $reachableRoles = $this->getReachableRoleNames($roles);

        foreach ($attributes as $attribute) {
            if (!\is_string($attribute) || !$this->supportsAttribute($attribute)) {
                continue;
            }

            $result = VoterInterface::ACCESS_DENIED;
            if (\in_array($attribute, $reachableRoles, true)) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return $result;
    }

    public function supportsAttribute(string $attribute): bool
    {
        return \str_starts_with($attribute, $this->roleStore->getPrefix());
    }

    public function supportsType(string $subjectType): bool
    {
        return true;
    }

    public function extractRoles(TokenInterface $token): array
    {
        return $token->getRoleNames();
    }

    public function getReachableRoleNames(array $roles): array
    {
        $key = \md5(\serialize($roles));
        $this->reachableRoles[$key] ??= $this->roleHierarchy->getReachableRoleNames($roles);
        return $this->reachableRoles[$key];
    }

    private function buildHierarchy(): RoleHierarchy
    {
        $src = $this->roleStore->getRoleHierarchy();
        $roleNames = $this->roleStore->getRoleNames();
        $hierarchy = [];
        foreach ($src as $role => $child) {
            if ($this->isRegex($child)) {
                $hierarchy[$role] = \preg_grep($child, $roleNames);
            } else if (\is_array($child)) {
                $hierarchy[$role] = $child;
            } else {
                throw new \RuntimeException('Unexpected role hierarchy');
            }
        }
        return new RoleHierarchy($hierarchy);
    }

    private function isRegex(mixed $subject): bool
    {
        return \is_string($subject) && @preg_match($subject, '') !== FALSE;
    }
}