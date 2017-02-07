<?php

namespace whatwedo\CrudBundle\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

/**
 * This is the class that validates and merges configuration from your app/config files.
 *
 * To learn more see {@link http://symfony.com/doc/current/cookbook/bundles/configuration.html}
 */
class Configuration implements ConfigurationInterface
{
    /**
     * {@inheritdoc}
     */
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('whatwedo_crud');

        $rootNode
            ->children()
                ->arrayNode('breadcrumbs')
                    ->children()
                        ->scalarNode('home_text')
                            ->defaultValue('Dashboard')
                        ->end()
                        ->scalarNode('home_route')
                            ->defaultValue('')
                        ->end()
                    ->end()
                ->end() // end breadcrumbs
            ->end()
        ;
        return $treeBuilder;
    }
}
