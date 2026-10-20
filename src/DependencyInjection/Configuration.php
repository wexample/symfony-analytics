<?php

namespace Wexample\SymfonyAnalytics\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;
use Wexample\SymfonyHelpers\Helper\RoleHelper;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('wexample_symfony_analytics');

        $treeBuilder->getRootNode()
            ->children()
                ->booleanNode('enabled')
                    ->info('Whether the pages viewed are counted.')
                    ->defaultTrue()
                ->end()
                ->scalarNode('access_role')
                    ->info('Who reads the audience: its page and its api.')
                    ->defaultValue(RoleHelper::ROLE_ADMIN)
                ->end()
                ->arrayNode('excluded_roles')
                    ->info('Accounts whose visits are not counted: the people running the site.')
                    ->scalarPrototype()->end()
                    ->defaultValue([RoleHelper::ROLE_ADMIN])
                ->end()
                ->integerNode('visit_timeout')
                    ->info('Minutes without a page after which the next one opens another visit.')
                    ->defaultValue(30)
                    ->min(1)
                ->end()
                ->integerNode('retention_months')
                    ->info('Months of pages viewed kept one by one; older ones are kept as monthly totals per dimension, then deleted.')
                    ->defaultValue(13)
                    ->min(1)
                ->end()
                ->booleanNode('auto_maintenance')
                    ->info('Rolls finished months up and deletes the expired pages once a day, after a response: no scheduler needed. Off to run analytics:maintain from one.')
                    ->defaultTrue()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
