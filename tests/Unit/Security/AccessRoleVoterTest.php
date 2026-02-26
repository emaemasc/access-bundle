<?php

namespace Ema\AccessBundle\Tests\Unit\Security;

use Ema\AccessBundle\Security\AccessRoleVoter;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use PHPUnit\Framework\Attributes\AllowMockObjectsWithoutExpectations;
use PHPUnit\Framework\TestCase;
use PHPUnit\Framework\MockObject\MockObject;
use Symfony\Component\Security\Core\Authentication\Token\TokenInterface;
use Symfony\Component\Security\Core\Authorization\Voter\VoterInterface;

#[AllowMockObjectsWithoutExpectations]
class AccessRoleVoterTest extends TestCase
{
    private MockObject|AccessRoleStore $roleStore;
    private AccessRoleVoter $voter;

    protected function setUp(): void
    {
        $this->roleStore = $this->createMock(AccessRoleStore::class);
        $this->voter = new AccessRoleVoter($this->roleStore);
    }

    public function testSupportsAttributeWithValidPrefix(): void
    {
        $this->roleStore->method('getPrefix')
            ->willReturn('EAB_');
            
        $this->assertTrue($this->voter->supportsAttribute('EAB_SOME_ROLE'));
    }

    public function testSupportsAttributeWithoutValidPrefix(): void
    {
        $this->roleStore->method('getPrefix')
            ->willReturn('EAB_');
            
        $this->assertFalse($this->voter->supportsAttribute('INVALID_PREFIX_ROLE'));
    }

    public function testSupportsTypeAlwaysReturnsTrue(): void
    {
        $this->assertTrue($this->voter->supportsType('AnyType'));
        $this->assertTrue($this->voter->supportsType(''));
        $this->assertTrue($this->voter->supportsType('AnotherType'));
    }

    public function testVoteWithNonStringAttribute(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')
              ->willReturn(['ROLE_USER']);
              
        $this->roleStore->method('getPrefix')
                        ->willReturn('EAB_');
        
        $result = $this->voter->vote($token, null, [123, 'INVALID_PREFIX_ROLE']);
        
        // The voter should abstain when encountering non-string attributes or attributes with invalid prefix
        $this->assertEquals(VoterInterface::ACCESS_ABSTAIN, $result);
    }

    public function testVoteWithUnrecognizedAttribute(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')
              ->willReturn(['ROLE_USER']);
              
        $this->roleStore->method('getPrefix')
                        ->willReturn('EAB_');
        $this->roleStore->method('getRoleNames')
                        ->willReturn(['EAB_USER_ROLE']);
        $this->roleStore->method('getRoleHierarchy')
                        ->willReturn([]);
        
        $result = $this->voter->vote($token, null, ['EAB_UNRECOGNIZED_ROLE']);
        
        $this->assertEquals(VoterInterface::ACCESS_DENIED, $result);
    }

    public function testVoteWithGrantedRole(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')
              ->willReturn(['EAB_GRANTED_ROLE']);
              
        $this->roleStore->method('getPrefix')
                        ->willReturn('EAB_');
        $this->roleStore->method('getRoleNames')
                        ->willReturn(['EAB_GRANTED_ROLE']);
        $this->roleStore->method('getRoleHierarchy')
                        ->willReturn([]);
        
        $result = $this->voter->vote($token, null, ['EAB_GRANTED_ROLE']);
        
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteWithSuperRole(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')
              ->willReturn(['ROLE_SUPER_ADMIN']);
              
        $this->roleStore->method('getPrefix')
                        ->willReturn('EAB_');
        $this->roleStore->method('getRoleNames')
                        ->willReturn(['EAB_SOME_ROLE']); // Available EAB roles for matching
        $this->roleStore->method('getRoleHierarchy')
                        ->willReturn([
                            'ROLE_SUPER_ADMIN' => '/.*/'  // This regex should match all EAB roles
                        ]);
        
        // Rebuild the voter to pick up the mocked values
        $this->voter = new AccessRoleVoter($this->roleStore);
        
        $result = $this->voter->vote($token, null, ['EAB_SOME_ROLE']);
        
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }

    public function testVoteWithMultipleAttributes(): void
    {
        $token = $this->createMock(TokenInterface::class);
        $token->method('getRoleNames')
              ->willReturn(['EAB_GRANTED_ROLE']);
              
        $this->roleStore->method('getPrefix')
                        ->willReturn('EAB_');
        $this->roleStore->method('getRoleNames')
                        ->willReturn(['EAB_GRANTED_ROLE', 'EAB_DENIED_ROLE']);
        $this->roleStore->method('getRoleHierarchy')
                        ->willReturn([]);
        
        $result = $this->voter->vote($token, null, ['EAB_DENIED_ROLE', 'EAB_GRANTED_ROLE']);
        
        // Should grant access if any of the attributes is granted
        $this->assertEquals(VoterInterface::ACCESS_GRANTED, $result);
    }
}