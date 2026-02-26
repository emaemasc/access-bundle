<?php

namespace Ema\AccessBundle;

use Ema\AccessBundle\Attribute\AsAccessGroupConfig;
use Ema\AccessBundle\Attribute\AsAccessPresetConfig;
use Ema\AccessBundle\Attribute\AsAccessRoleStore;
use Ema\AccessBundle\Command\MigrateRolesCommand;
use Ema\AccessBundle\Contracts\AccessGroupConfig;
use Ema\AccessBundle\Contracts\AccessPresetConfig;
use Ema\AccessBundle\Contracts\AccessRoleStore;
use Ema\AccessBundle\DependencyInjection\AccessCompilerPass;
use Ema\AccessBundle\EventListener\AccessAttributeListener;
use Ema\AccessBundle\Form\AccessType;
use Ema\AccessBundle\Group\DefaultAccessGroupConfig;
use Ema\AccessBundle\Preset\DefaultAccessPresetConfig;
use Ema\AccessBundle\Role\DefaultAccessRoleStore;
use Ema\AccessBundle\Security\AccessRoleVoter;
use Symfony\Component\DependencyInjection\ContainerBuilder;
use Symfony\Component\DependencyInjection\Definition;
use Symfony\Component\DependencyInjection\Loader\Configurator\ContainerConfigurator;
use Symfony\Component\HttpKernel\Bundle\AbstractBundle;
use Symfony\Component\HttpKernel\KernelEvents;
use function Symfony\Component\DependencyInjection\Loader\Configurator\service;

class EmaAccessBundle extends AbstractBundle
{
    public const NAME = 'emaemasc_access';

    public function prependExtension(ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $builder->prependExtensionConfig('twig', [
            'form_themes' => ['@EmaAccess/form/default.html.twig'],
        ]);
    }

    public function loadExtension(array $config, ContainerConfigurator $container, ContainerBuilder $builder): void
    {
        $this->registerDefaultServices($builder);
        $this->registerBundleServices($container);
    }

    public function build(ContainerBuilder $container): void
    {
        $container->registerAttributeForAutoconfiguration(
            AsAccessGroupConfig::class,
            static function (Definition $definition, AsAccessGroupConfig $attribute, \ReflectionClass $reflector) use ($container): void {
                if (!$reflector->implementsInterface(AccessGroupConfig::class)) {
                    throw new \ErrorException(sprintf('Service "%s" must implement interface "%s"', $reflector->getName(), AccessGroupConfig::class));
                }
                $container->setAlias(AccessGroupConfig::class, $reflector->getName());
            }
        );

        $container->registerAttributeForAutoconfiguration(
            AsAccessPresetConfig::class,
            static function (Definition $definition, AsAccessPresetConfig $attribute, \ReflectionClass $reflector) use ($container): void {
                if (!$reflector->implementsInterface(AccessPresetConfig::class)) {
                    throw new \ErrorException(sprintf('Service "%s" must implement interface "%s"', $reflector->getName(), AccessPresetConfig::class));
                }
                $container->setAlias(AccessPresetConfig::class, $reflector->getName());
            }
        );

        $container->registerAttributeForAutoconfiguration(
            AsAccessRoleStore::class,
            static function (Definition $definition, AsAccessRoleStore $attribute, \ReflectionClass $reflector) use ($container): void {
                if (!$reflector->implementsInterface(AccessRoleStore::class)) {
                    throw new \ErrorException(sprintf('Service "%s" must implement interface "%s"', $reflector->getName(), AccessRoleStore::class));
                }
                $container->setAlias(AccessRoleStore::class, $reflector->getName());
            }
        );

        $container->addCompilerPass(new AccessCompilerPass());
    }

    private function registerDefaultServices(ContainerBuilder $builder): void
    {
        if (!$builder->hasDefinition(AccessGroupConfig::class)) {
            $builder->register(AccessGroupConfig::class, DefaultAccessGroupConfig::class)
                ->setPublic(true);
        }

        if (!$builder->hasDefinition(AccessPresetConfig::class)) {
            $builder->register(AccessPresetConfig::class, DefaultAccessPresetConfig::class)
                ->setPublic(true);
        }

        if (!$builder->hasDefinition(AccessRoleStore::class)) {
            $builder->register(AccessRoleStore::class, DefaultAccessRoleStore::class)
                ->setPublic(true);
        }
    }

    private function registerBundleServices(ContainerConfigurator $container): void
    {
        $services = $container->services();
        $services->defaults()->autowire();

        $services->set(MigrateRolesCommand::class)->tag('console.command');
        $services->set(AccessType::class)->tag('form.type');

        $services->set(AccessRoleVoter::class)
            ->arg(0, service(AccessRoleStore::class))
            ->tag('security.voter');

        $services->set(AccessAttributeListener::class)
            ->arg(0, service('security.authorization_checker'))
            ->tag('kernel.event_listener', ['event' => KernelEvents::CONTROLLER_ARGUMENTS]);
    }
}
