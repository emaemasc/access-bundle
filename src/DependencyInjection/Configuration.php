<?php

namespace Ema\AccessBundle\DependencyInjection;

use Ema\AccessBundle\EmaAccessBundle;
use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder(EmaAccessBundle::NAME);
        $rootNode = $treeBuilder->getRootNode();

        $rootNode
            ->children()
                ->arrayNode('entities')
                    ->children()
                        ->scalarNode('role')
                            ->info('AccessRole entity FQCN')
                        ->end()
                    ->end()
                ->end()
                ->arrayNode('settings')
                    ->children()
                        ->scalarNode('group')
                            ->info('AccessGroup config FQCN')
                        ->end()
                        ->scalarNode('preset')
                            ->info('AccessPreset config FQCN')
                        ->end()
                    ->end()
                ->end()
            ->end()
        ;

        return $treeBuilder;
    }
}
