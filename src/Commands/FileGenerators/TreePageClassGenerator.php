<?php

namespace SolutionForest\FilamentNestableTree\Commands\FileGenerators;

class TreePageClassGenerator extends BaseTreeClassGenerator
{
    final public function __construct(
        protected string $fqn,
        protected ?string $resourceFqn,
        protected ?string $clusterFqn,
        protected ?string $modelFqn,
        protected ?string $staticNodesPropertyName,
        protected ?array $multipleTreeKeys = null,
    ) {
        parent::__construct(
            isPage: true,
            fqn: $fqn,
            resourceFqn: $resourceFqn,
            clusterFqn: $clusterFqn,
            modelFqn: $modelFqn,
            staticNodesPropertyName: $staticNodesPropertyName,
            multipleTreeKeys: $multipleTreeKeys,
        );
    }
}
