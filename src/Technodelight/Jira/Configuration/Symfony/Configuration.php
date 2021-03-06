<?php

namespace Technodelight\Jira\Configuration\Symfony;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('jira');

        $rootNode
            ->children()
                ->append($this->credentialsSection())
                ->append($this->integrationsSection())
                ->append($this->projectSection())
                ->append($this->transitionsSection())
                ->append($this->aliasesSection())
                ->append($this->filtersSection())
            ->end()
        ->end();

        return $treeBuilder;
    }

    private function credentialsSection()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('credentials');

        $rootNode
            ->info('JIRA connection credentials')
            ->children()
                ->scalarNode('domain')
                    ->info('JIRA\'s domain without protocol, like something.atlassian.net')
                    ->example('something.atlassian.net')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('username')
                    ->info('Your JIRA username')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
                ->scalarNode('password')
                    ->info('Your JIRA password')
                    ->isRequired()
                    ->cannotBeEmpty()
                ->end()
            ->end()
        ->end();

        return $rootNode;
    }

    private function integrationsSection()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('integrations');

        $rootNode
            ->info('Third party integration configs')
            ->children()
                ->arrayNode('github')
                    ->info('GitHub credentials - used to retrieve pull request data, including webhook statuses. Visit this page to generate a token: https://github.com/settings/tokens/new?scopes=repo&description=jira+cli+tool')
                    ->children()
                        ->scalarNode('apiToken')->isRequired()->end()
                    ->end()
                ->end()
            ->end();

        return $rootNode;
    }

    private function projectSection()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('project');

        $rootNode
            ->info('Project specific settings')
            ->addDefaultsIfNotSet()
            ->children()
                ->scalarNode('yesterdayAsWeekday')
                    ->info('Using \'yesterday\' means last workday on monday')
                    ->defaultTrue()
                ->end()
                ->scalarNode('defaultWorklogTimestamp')
                    ->info('Default worklog timestamp to use if date is omitted')
                    ->defaultValue('now')
                ->end()
                ->scalarNode('cacheTtl')
                    ->info('keep API data in caches')
                    ->defaultValue(15 * 60)
                ->end()
            ->end();

        return $rootNode;
    }

    private function transitionsSection()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('transitions');

        $rootNode
            ->info('Issue transitions registered as commands')
            ->prototype('array')
                ->children()
                    ->scalarNode('command')->cannotBeEmpty()->isRequired()->end()
                    ->scalarNode('transition')->cannotBeEmpty()->isRequired()->end()
                ->end()
            ->end();

        return $rootNode;
    }

    private function aliasesSection()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('aliases');

        $rootNode
            ->info('Use named issues instead of numbers. Can be used anywhere where issueKey is a command\'s input')
            ->prototype('array')
                ->children()
                    ->scalarNode('alias')->cannotBeEmpty()->isRequired()->end()
                    ->scalarNode('issueKey')->cannotBeEmpty()->isRequired()->end()
                ->end()
            ->end();

        return $rootNode;
    }

    private function filtersSection()
    {
        $treeBuilder = new TreeBuilder();
        $rootNode = $treeBuilder->root('filters');

        $rootNode
            ->info('Custom quick filters registered as commands')
            ->prototype('array')
                ->children()
                    ->scalarNode('command')->cannotBeEmpty()->isRequired()->end()
                    ->scalarNode('jql')->cannotBeEmpty()->isRequired()->end()
                ->end()
            ->end();

        return $rootNode;
    }

}
