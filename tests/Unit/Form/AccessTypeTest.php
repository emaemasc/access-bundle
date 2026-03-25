<?php

namespace Ema\AccessBundle\Tests\Unit\Form;

use Ema\AccessBundle\Contracts\AccessGroupConfig;
use Ema\AccessBundle\Contracts\AccessPresetConfig;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\Dto\AccessGroupDto;
use Ema\AccessBundle\Dto\AccessPresetDto;
use Ema\AccessBundle\Dto\AccessRoleDto;
use Ema\AccessBundle\Form\AccessType;
use PHPUnit\Framework\TestCase;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;

class AccessTypeTest extends TestCase
{
    public function testBuildViewKeepsUngroupedRolesWhenSomeGroupsExist(): void
    {
        $roleStore = $this->createMock(AccessRoleStore::class);
        $groupConfig = $this->createMock(AccessGroupConfig::class);
        $presetConfig = $this->createMock(AccessPresetConfig::class);

        $group = AccessGroupDto::new('users', 'Users', 10);
        $preset = AccessPresetDto::new('admin', 'Admin', 10);

        $roleStore->method('getRoles')->willReturn([
            'EAB_User_index' => new AccessRoleDto('EAB_User_index', 'List users', props: null, group: 'users', presets: ['admin']),
            'EAB_User_edit' => new AccessRoleDto('EAB_User_edit', 'Edit users', props: null, group: 'missing', presets: null),
        ]);
        $groupConfig->method('all')->willReturn(['users' => $group]);
        $presetConfig->method('all')->willReturn(['admin' => $preset]);

        $type = new AccessType($roleStore, $groupConfig, $presetConfig);

        $view = new FormView();
        $type->buildView($view, $this->createMock(FormInterface::class), ['toggle_attributes' => []]);

        self::assertSame(['EAB_User_edit'], $view->vars['ungrouped_items']);
        self::assertCount(1, $view->vars['groups']);
        self::assertSame('users', $view->vars['groups'][0]->name);
        self::assertSame(['EAB_User_index'], $view->vars['groups'][0]->items);
        self::assertCount(1, $view->vars['presets']);
        self::assertSame(['EAB_User_index'], $view->vars['presets']['admin']->items);
    }

    public function testBuildViewFiltersRolesByArrayCriteria(): void
    {
        $roleStore = $this->createMock(AccessRoleStore::class);
        $groupConfig = $this->createMock(AccessGroupConfig::class);
        $presetConfig = $this->createMock(AccessPresetConfig::class);

        $group = AccessGroupDto::new('users', 'Users', 10);
        $preset = AccessPresetDto::new('admin', 'Admin', 10);

        $roleStore->method('getRoles')->willReturn([
            'EAB_User_index' => new AccessRoleDto('EAB_User_index', 'List users', props: ['scope' => 'admin'], group: 'users', presets: ['admin']),
            'EAB_User_edit' => new AccessRoleDto('EAB_User_edit', 'Edit users', props: ['scope' => 'editor'], group: 'users', presets: ['admin']),
        ]);
        $groupConfig->method('all')->willReturn(['users' => $group]);
        $presetConfig->method('all')->willReturn(['admin' => $preset]);

        $type = new AccessType($roleStore, $groupConfig, $presetConfig);

        $view = new FormView();
        $type->buildView($view, $this->createMock(FormInterface::class), [
            'toggle_attributes' => [],
            'role_filter' => ['props[scope]' => 'editor'],
        ]);

        self::assertSame(['EAB_User_edit'], array_keys($view->vars['items']));
        self::assertSame(['EAB_User_edit'], $view->vars['groups'][0]->items);
        self::assertSame(['EAB_User_edit'], $view->vars['presets']['admin']->items);
        self::assertSame([], $view->vars['ungrouped_items']);
    }

    public function testBuildFormFiltersRolesByCallable(): void
    {
        $roleStore = $this->createMock(AccessRoleStore::class);
        $groupConfig = $this->createMock(AccessGroupConfig::class);
        $presetConfig = $this->createMock(AccessPresetConfig::class);

        $roleStore->method('getRoles')->willReturn([
            'EAB_User_index' => new AccessRoleDto('EAB_User_index', 'List users', props: null, group: 'users', presets: ['admin']),
            'EAB_User_edit' => new AccessRoleDto('EAB_User_edit', 'Edit users', props: null, group: 'users', presets: ['admin']),
        ]);
        $groupConfig->method('all')->willReturn([]);
        $presetConfig->method('all')->willReturn([]);

        $type = new AccessType($roleStore, $groupConfig, $presetConfig);

        $builder = $this->getMockBuilder(\Symfony\Component\Form\FormBuilderInterface::class)
            ->getMock();

        $builder->expects($this->once())
            ->method('add')
            ->with(
                'item_EAB_User_edit',
                \Symfony\Component\Form\Extension\Core\Type\CheckboxType::class,
                $this->callback(static fn (array $options): bool => $options['label'] === 'Edit users')
            );
        $builder->expects($this->once())
            ->method('addModelTransformer');

        $type->buildForm($builder, [
            'role_filter' => static fn (AccessRoleDto $role): bool => $role->getGroup() === 'users' && $role->getTitle() === 'Edit users',
        ]);
    }
}
