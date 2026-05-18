<?php

namespace SolutionForest\FilamentNestableTree\Commands\FileGenerators;

use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Clusters\Cluster;
use Filament\Support\Commands\FileGenerators\ClassGenerator;
use Nette\PhpGenerator\ClassType;
use Nette\PhpGenerator\Literal;
use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Filament\Resources\Pages\TreePage as ResourceTreePage;
use SolutionForest\FilamentNestableTree\Tree;

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
