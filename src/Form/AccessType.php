<?php

namespace Ema\AccessBundle\Form;

use Ema\AccessBundle\Dto\AccessGroupDto;
use Ema\AccessBundle\Dto\AccessPresetDto;
use Ema\AccessBundle\Dto\AccessRoleDto;
use Ema\AccessBundle\Contracts\AccessGroupConfig;
use Ema\AccessBundle\Contracts\AccessPresetConfig;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\EmaAccessBundle;
use Ema\AccessBundle\Form\DataTransformer\AccessRoleDataTransformer;
use Symfony\Component\Form\AbstractType;
use Symfony\Component\Form\Extension\Core\Type\CheckboxType;
use Symfony\Component\Form\FormBuilderInterface;
use Symfony\Component\Form\FormInterface;
use Symfony\Component\Form\FormView;
use Symfony\Component\OptionsResolver\OptionsResolver;
use Symfony\Component\PropertyAccess\Exception\AccessException;
use Symfony\Component\PropertyAccess\Exception\NoSuchIndexException;
use Symfony\Component\PropertyAccess\Exception\NoSuchPropertyException;
use Symfony\Component\PropertyAccess\Exception\UninitializedPropertyException;
use Symfony\Component\PropertyAccess\Exception\UnexpectedTypeException;
use Symfony\Component\PropertyAccess\PropertyAccess;
use Symfony\Component\PropertyAccess\PropertyAccessorInterface;

class AccessType extends AbstractType
{
    private array $items;
    private array $groups;
    private array $presets;
    private readonly PropertyAccessorInterface $propertyAccessor;

    public function __construct(
        private readonly AccessRoleStore    $roleStore,
        private readonly AccessGroupConfig  $groupConfig,
        private readonly AccessPresetConfig $presetConfig,
    ) {
        $this->propertyAccessor = PropertyAccess::createPropertyAccessor();
        $this->items = $this->roleStore->getRoles();
        $this->groups = $this->groupConfig->all();
        $this->presets = $this->presetConfig->all();
    }

    public function buildForm(FormBuilderInterface $builder, array $options): void
    {
        foreach ($this->getFilteredItems($options['role_filter'] ?? null) as $item) {
            $builder->add('item_' . $item->getName(), CheckboxType::class, [
                'label' => $item->getTitle(),
                'required' => false,
                'attr' => [
                    'class' => 'emaemasc-input',
                    'data-item-id' => $item->getName(),
                    'data-item-name' => $item->getName(),
                    'data-item-title' => $item->getTitle()
                ],
                'label_attr' => [
                    'class' => 'emaemasc-input-label'
                ],
                'property_path' => '[' . $item->getName() . ']'
            ]);
        }

        $builder->addModelTransformer(new AccessRoleDataTransformer($this->roleStore));
    }

    public function buildView(FormView $view, FormInterface $form, array $options): void
    {
        $data = $this->buildData($options['role_filter'] ?? null);

        $view->vars['items'] = $data['items'];
        $view->vars['groups'] = $data['groups'];
        $view->vars['presets'] = $options['show_presets'] ? $data['presets'] : [];
        $view->vars['ungrouped_items'] = $data['ungrouped_items'];
        $view->vars['toggle_attributes'] = $options['toggle_attributes'] ?? [];
    }

    public function configureOptions(OptionsResolver $resolver): void
    {
        $resolver->setDefaults([
            'data_class' => null,
            'toggle_attributes' => [],
            'role_filter' => null,
            'show_presets' => true,
            'compound' => true,
        ]);

        $resolver->setAllowedTypes('toggle_attributes', 'array');
        $resolver->setAllowedTypes('role_filter', ['null', 'array', 'callable']);
        $resolver->setAllowedTypes('show_presets', 'bool');
    }

    public function getBlockPrefix(): string
    {
        return EmaAccessBundle::NAME;
    }

    private function getFilteredItems(null|array|callable $roleFilter): array
    {
        if (null === $roleFilter) {
            return $this->items;
        }

        if (\is_array($roleFilter)) {
            return \array_filter(
                $this->items,
                fn (AccessRoleDto $role): bool => $this->matchesRoleCriteria($role, $roleFilter)
            );
        }

        return \array_filter(
            $this->items,
            fn (AccessRoleDto $role): bool => $this->invokeRoleFilter($roleFilter, $role)
        );
    }

    private function buildData(null|array|callable $roleFilter): array
    {
        $items = $this->getFilteredItems($roleFilter);
        $groups = $this->cloneGroups();
        $presets = $this->clonePresets();
        $ungroupedItems = [];

        foreach ($items as $item) {
            $group = $item->getGroup();
            if ($group && isset($groups[$group])) {
                $groups[$group]->items[] = $item->getName();
            } else {
                $ungroupedItems[] = $item->getName();
            }

            foreach ($item->getPresets() ?? [] as $preset) {
                if (isset($presets[$preset])) {
                    $presets[$preset]->items[] = $item->getName();
                }
            }
        }

        foreach ($groups as $key => $group) {
            if (empty($group->items)) {
                unset($groups[$key]);
            }
        }

        foreach ($presets as $key => $preset) {
            if (empty($preset->items)) {
                unset($presets[$key]);
            }
        }

        \usort($groups, static fn (AccessGroupDto $a, AccessGroupDto $b): int => $a->sort <=> $b->sort);

        return [
            'items' => $items,
            'groups' => $groups,
            'presets' => $presets,
            'ungrouped_items' => $ungroupedItems,
        ];
    }

    private function cloneGroups(): array
    {
        return \array_map(static fn (AccessGroupDto $group): AccessGroupDto => clone $group, $this->groups);
    }

    private function clonePresets(): array
    {
        return \array_map(static fn (AccessPresetDto $preset): AccessPresetDto => clone $preset, $this->presets);
    }

    private function invokeRoleFilter(callable $roleFilter, AccessRoleDto $role): bool
    {
        return (bool) \call_user_func($roleFilter, $role);
    }

    private function matchesRoleCriteria(AccessRoleDto $role, array $criteria): bool
    {
        foreach ($criteria as $property => $expected) {
            $actual = $this->readRoleProperty($role, (string) $property);

            if (\is_array($expected)) {
                if (\is_array($actual)) {
                    if (\array_diff($expected, $actual) !== []) {
                        return false;
                    }
                    continue;
                }

                if (!\in_array($actual, $expected, true)) {
                    return false;
                }

                continue;
            }

            if (\is_array($actual)) {
                if (!\in_array($expected, $actual, true)) {
                    return false;
                }

                continue;
            }

            if ($actual !== $expected) {
                return false;
            }
        }

        return true;
    }

    private function readRoleProperty(AccessRoleDto $role, string $property): mixed
    {
        try {
            return $this->propertyAccessor->getValue($role, $property);
        } catch (AccessException|NoSuchPropertyException|NoSuchIndexException|UninitializedPropertyException|UnexpectedTypeException) {
            return null;
        } catch (\TypeError) {
            return null;
        }
    }

    private function reflectCallable(callable $callable): ?\ReflectionFunctionAbstract
    {
        if ($callable instanceof \Closure) {
            return new \ReflectionFunction($callable);
        }

        if (\is_string($callable)) {
            return new \ReflectionFunction($callable);
        }

        if (\is_array($callable) && \count($callable) === 2) {
            return new \ReflectionMethod($callable[0], $callable[1]);
        }

        if (\is_object($callable) && \method_exists($callable, '__invoke')) {
            return new \ReflectionMethod($callable, '__invoke');
        }

        return null;
    }
}
