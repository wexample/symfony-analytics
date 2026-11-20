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
                ->integerNode('robot_hourly_views')
                    ->info('Pages within an hour past which a visitor is counted as a suspected robot, whatever its user agent says: no person reads that fast. 0 turns it off.')
                    ->defaultValue(60)
                    ->min(0)
                ->end()
                ->integerNode('robot_minute_views')
                    ->info('Pages within a minute past which a visitor is counted as a suspected robot: a burst no person clicks through. 0 turns it off.')
                    ->defaultValue(15)
                    ->min(0)
                ->end()
                ->integerNode('retention_months')
                    ->info('Months the people\'s pages viewed are kept one by one; older ones are kept as monthly totals per dimension, then deleted.')
                    ->defaultValue(13)
                    ->min(1)
                ->end()
                ->integerNode('suspect_retention_months')
                    ->info('Months the suspected robots\' pages viewed are kept one by one, before only their monthly totals are.')
                    ->defaultValue(1)
                    ->min(1)
                ->end()
                ->integerNode('robot_retention_months')
                    ->info('Months the declared robots\' pages are kept as a sum a day per robot and route, before only their monthly totals are. 0 does not record them.')
                    ->defaultValue(3)
                    ->min(0)
                ->end()
                ->arrayNode('api_paths')
                    ->info('The paths of the api, as patterns: a call under one is counted as a call to the api, not as a page.')
                    ->scalarPrototype()->end()
                    ->defaultValue(['^/api/'])
                ->end()
                ->arrayNode('api_excluded_routes')
                    ->info('The api\'s routes not counted: the probes of its health would outnumber its callers.')
                    ->scalarPrototype()->end()
                    ->defaultValue(['api_health'])
                ->end()
                ->integerNode('api_retention_months')
                    ->info('Months the calls to the api are kept, a sum a day per route, client, status and cache answer. 0 does not count them.')
                    ->defaultValue(13)
                    ->min(0)
                ->end()
                ->booleanNode('auto_maintenance')
                    ->info('Rolls finished months up and deletes the expired pages once a day, after a response: no scheduler needed. Off to run analytics:maintain from one.')
                    ->defaultTrue()
                ->end()
            ->end();

        return $treeBuilder;
    }
}
