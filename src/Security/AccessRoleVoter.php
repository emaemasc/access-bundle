<?php

namespace Ema\AccessBundle\Security;

use Ema\AccessBundle\Contracts\AccessRoleStore;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\CacheableVoterInterface;
use Symfony\Component\Security\Core\Authorization\Voter\Vote;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

class AccessRoleVoter implements CacheableVoterInterface
{
    private string $prefix = 'EAB_';

    public function __construct(
        private readonly AccessRoleStore $roleStore,
    ) {
    }

    public function vote(TokenInterface $token, mixed $subject, array $attributes, ?Vote $vote = null): int
    {
        $result = VoterInterface::ACCESS_ABSTAIN;
        $roles = $this->extractRoles($token);

        foreach ($attributes as $attribute) {
            if (!\is_string($attribute) || !$this->supportsAttribute($attribute)) {
                continue;
            }

            foreach ($this->roleStore->getSuperRoles() as $superRole) {
                if (\in_array($superRole, $roles, true)) {
                    return VoterInterface::ACCESS_GRANTED;
                }
            }

            $result = VoterInterface::ACCESS_DENIED;
            if (\in_array($attribute, $roles, true)) {
                return VoterInterface::ACCESS_GRANTED;
            }
        }

        return $result;
    }

    public function supportsAttribute(string $attribute): bool
    {
        return str_starts_with($attribute, $this->prefix);
    }

    public function supportsType(string $subjectType): bool
    {
        return true;
    }

    protected function extractRoles(TokenInterface $token): array
    {
        return $token->getRoleNames();
    }
}