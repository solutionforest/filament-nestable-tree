<?php

namespace SolutionForest\FilamentNestableTree\Commands\FileGenerators;

class TreeWidgetClassGenerator extends BaseTreeClassGenerator
{
    final public function __construct(
        protected string $fqn,
        protected ?string $modelFqn,
        protected ?string $staticNodesPropertyName,
    ) {
        parent::__construct(
            isPage: false,
            fqn: $fqn,
            resourceFqn: null, // No need for resource FQN for widget
            clusterFqn: null, // No need for cluster FQN for widget
            modelFqn: $modelFqn,
            staticNodesPropertyName: $staticNodesPropertyName,
            multipleTreeKeys: [], // Disabled multiple tree
        );
    }
}
