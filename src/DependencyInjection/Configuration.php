<?php

declare(strict_types=1);

namespace RequestDtoResolver\DependencyInjection;

use Symfony\Component\Config\Definition\Builder\TreeBuilder;
use Symfony\Component\Config\Definition\ConfigurationInterface;

final class Configuration implements ConfigurationInterface
{
    public function getConfigTreeBuilder(): TreeBuilder
    {
        $treeBuilder = new TreeBuilder('request_dto_resolver');

        $treeBuilder->getRootNode()
            ->children()
                ->scalarNode('target_dto_interface')->cannotBeEmpty()->end()
            ->end();

        return $treeBuilder;
    }
}
