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
use SolutionForest\FilamentNestableTree\Filament\Widgets\Tree as WidgetTree;
use SolutionForest\FilamentNestableTree\Tree;

abstract class BaseTreeClassGenerator extends ClassGenerator
{
    public function __construct(
        protected bool $isPage,
        protected string $fqn,
        protected ?string $resourceFqn,
        protected ?string $clusterFqn,
        protected ?string $modelFqn,
        protected ?string $staticNodesPropertyName,
        protected ?array $multipleTreeKeys = null,
    ) {}

    /**
     * @return array<string>
     */
    public function getImports(): array
    {
        $imports = [
            Tree::class,
            Action::class,
            CreateAction::class,
            ...($this->hasResource() ? [$this->resourceFqn] : []),
        ];

        $extends = $this->getExtends();
        $extendsBasename = class_basename($extends);
        // If current class name is same as the extends class, we need to alias the extends class to avoid naming conflict
        if ($extendsBasename === $this->getBasename() || ! $this->isCreatingPageTree()) {
            $imports[$extends] = "Base{$extendsBasename}";
        } else {
            $imports[] = $extends;
        }

        if (($modelFqn = $this->getModelFqn()) && filled($modelFqn)) {
            // If the model class name is same as the page class name, we need to alias the model class to avoid naming conflict
            if ($this->getModelBasename() !== $this->getImportModelBasename()) {
                $imports[$modelFqn] = $this->getImportModelBasename();
            } else {
                $imports[] = $modelFqn;
            }
        }

        if ($this->hasCluster() && $this->isCreatingPageTree()) {
            if ($this->getClusterBasename() === 'Page') {
                $imports[$this->getClusterFqn()] = 'PageCluster';
            } else {
                $imports[] = $this->getClusterFqn();
            }
        }

        return $imports;
    }

    public function getExtends(): string
    {
        if ($this->isCreatingPageTree()) {
            if ($this->hasResource()) {
                return ResourceTreePage::class;
            }

            return TreePage::class;
        } else {
            return WidgetTree::class;
        }
    }

    protected function addPropertiesToClass(ClassType $class): void
    {
        if ($this->hasResource()) {
            // Add static property resource
            $class->addProperty('resource', new Literal(class_basename($this->resourceFqn) . '::class'))
                ->setType('string')
                ->setStatic()
                ->setProtected();
        } else {
            // Set default navigation icon
            $class->addProperty('navigationIcon', 'heroicon-o-document')
                ->setType('\BackedEnum|string|null')
                ->setStatic()
                ->setProtected();
        }

        if ($this->isStaticNode()) {
            $defaultTreeValues = [
                ['id' => 1, 'parent_id' => null, 'name' => 'Node 1'],
                ['id' => 2, 'parent_id' => null, 'name' => 'Node 2'],
                ['id' => 3, 'parent_id' => 1, 'name' => 'Node 1.1'],
                ['id' => 4, 'parent_id' => 1, 'name' => 'Node 1.2'],
                ['id' => 5, 'parent_id' => 3, 'name' => 'Node 1.1.1',
                    'children' => [
                        ['id' => 6, 'parent_id' => 5, 'name' => 'Node 1.1.1.1',
                            'children' => [
                                ['id' => 7, 'parent_id' => 6, 'name' => 'Node 1.1.1.1.1'],
                            ]],
                        ['id' => 8, 'parent_id' => 5, 'name' => 'Node 1.1.1.2'],
                    ],
                ],
            ];
            if ($this->isMultipleTrees()) {
                $defaultValue = array_fill_keys($this->multipleTreeKeys, $defaultTreeValues);
            } else {
                $defaultValue = $defaultTreeValues;
            }
            $class->addProperty($this->getFormattedStaticNodesPropertyName(), $defaultValue)
                ->setType('array')
                ->setPublic();
        }
    }

    protected function addMethodsToClass(ClassType $class): void
    {
        if (! $this->isMultipleTrees()) {
            $method = $class->addMethod('tree')
                ->setPublic()
                ->setReturnType(Tree::class)
                ->setBody('return ?;', [
                    new Literal($this->buildTreeMethodBody('$tree')),
                ]);

            $method->addParameter('tree')

                ->setType(Tree::class);
        } else {

            $class->addMethod('trees')
                ->setPublic()
                ->setReturnType('array')
                ->setBody('return ?;', [
                    collect($this->multipleTreeKeys)
                        ->mapWithKeys(function ($key) {
                            return [$key => new Literal($this->buildTreeMethodBody('Tree::make()', $key))];
                        })
                        ->toArray(),
                ]);
        }
    }

    protected function isCreatingPageTree(): bool
    {
        return $this->isPage;
    }

    public function getNamespace(): string
    {
        return $this->extractNamespace($this->getFqn());
    }

    public function getBasename(): string
    {
        return class_basename($this->getFqn());
    }

    protected function getFqn(): string
    {
        return $this->fqn;
    }

    /**
     * @return ?class-string<Cluster>
     */
    public function getClusterFqn(): ?string
    {
        return $this->clusterFqn;
    }

    public function getClusterBasename(): string
    {
        return class_basename($this->getClusterFqn());
    }

    public function hasCluster(): bool
    {
        return filled($this->getClusterFqn());
    }

    public function hasResource(): bool
    {
        return filled($this->resourceFqn);
    }

    protected function isStaticNode(): bool
    {
        return ! empty($this->staticNodesPropertyName);
    }

    protected function isMultipleTrees(): bool
    {
        return ! empty($this->multipleTreeKeys);
    }

    /**
     * Formats the static nodes property name by ensuring it does not have a leading '$' and is a valid PHP variable name.
     *
     * e.g. '$nodes' or 'nodes' would both be formatted to 'nodes'.
     */
    protected function getFormattedStaticNodesPropertyName(): ?string
    {
        if (empty($this->staticNodesPropertyName)) {
            return null;
        }

        return str($this->staticNodesPropertyName)
            ->ltrim('$')
            ->toString();
    }

    public function getModelFqn(): ?string
    {
        return filled($this->modelFqn) ? $this->modelFqn : null;
    }

    public function getModelBasename(): ?string
    {
        $modelFqn = $this->getModelFqn();

        return filled($modelFqn) ? class_basename($modelFqn) : null;
    }

    protected function getImportModelBasename(): ?string
    {
        $modelClassName = $this->getModelBasename();
        if (empty($modelClassName)) {
            return null;
        }

        // If the model class name is same as the page class name, we need to alias the model class to avoid naming conflict
        if ($modelClassName === $this->getBasename()) {
            return 'TreeModel';
        }

        return $modelClassName;
    }

    protected function buildTreeMethodBody(string $treeConfigProp, ?string $multipleTreeKey = null)
    {
        $args = [];
        $body = <<<PHP
            {$treeConfigProp}
            PHP;

        // Nodes setting
        if ($this->isStaticNode()) {
            $body .= <<<'PHP'

                ->records(?)
                ->saveOrderUsing(function (array $nodes) {
                    ? = $nodes;
                })
            PHP;
            $staticNodePropName = $this->getFormattedStaticNodesPropertyName();
            $staticNodeProp = "\$this->{$staticNodePropName}";
            if ($this->isMultipleTrees() && filled($multipleTreeKey)) {
                $staticNodeProp .= "['{$multipleTreeKey}']";
            }
            $args[] = new Literal("fn () => {$staticNodeProp} ?? []");
            $args[] = new Literal($staticNodeProp);

        } else {
            $body .= <<<'PHP'

                ->model(?)
            PHP;
            if (($modelBasename = $this->getImportModelBasename()) && filled($modelBasename)) {
                $args[] = new Literal("{$modelBasename}::class");
            } elseif ($this->hasResource()) {
                $args[] = new Literal('static::getModel()');
            }
        }

        $body .= <<<'PHP'

                ->labelField(?)
                ->searchable()
                ->draggable()
                ->maxDepth(10)
                ->maxVisibleDepth(4)
                ->appendToolbarActions(fn ($tree) => [
                    ?
                ])
            PHP;
        // Label field
        $args[] = $this->hasResource()
            ? new Literal('static::getResource()::getRecordTitleAttribute()') :
             'name';

        // Add sample create button
        $createActionName = $multipleTreeKey ? "create-{$multipleTreeKey}" : 'create';
        $createActionFunc = <<<PHP
                CreateAction::make('{$createActionName}')
                    ->extraAttributes(['style' => 'margin-left: auto;'])
                    ->after(fn (\$livewire) => \$livewire->dispatch('tree-refresh'))
        PHP;
        if (! $this->hasResource() || $this->isMultipleTrees()) {
            $createActionFunc .= <<<PHP
        
                    ->schema([
                        \Filament\Forms\Components\TextInput::make(\$tree?->getLabelField() ?? 'name')->required(),
                    ])
        PHP;
        }
        if ($this->isStaticNode()) {
            $createActionFunc .= <<<PHP

                    ->action(function (array \$data, \$action) use (\$tree) {
                        // Handle node creation logic here
                        // For example, if using static nodes:
                        \$newNode = [
                            'id' => rand(1000, 9999), // Generate a random ID for demo purposes
                            'parent_id' => null, // Set parent_id as needed
                            'name' => \$data['name'],
                        ];
                        \Filament\Notifications\Notification::make()
                            ->title('New Node Data')
                            ->body(json_encode(\$newNode))
                            ->info()
                            ->send();
                        \$action->halt();
                    }),
        PHP;
        } else {
            $createActionFunc .= <<<PHP

                    ->action(function (array \$data, \$model, \$action) use (\$tree) {
                            \$model ??= \$tree->getModel();
                            if (empty(\$model)) {
                                \Filament\Notifications\Notification::make()
                                    ->title('Model Not Found')
                                    ->body('Unable to determine the model for creating a new node.')
                                    ->danger()
                                    ->send();
                                \$action->halt();
                                return;
                            }
                            \$model::create(\$data);
                    }),
        PHP;
        }
        $args[] = new Literal($createActionFunc);

        // Add sample node actions
        if (! $this->hasResource()) {
            $body .= <<<'PHP'

                ->nodeActions(fn (Tree $tree) => [
                    ?
                ])
            PHP;
            $args[] = new Literal(<<<PHP
                    Action::make('rename')
                        ->iconButton()
                        ->icon('heroicon-o-pencil')
                        ->color('gray')
                        ->schema([
                            \Filament\Forms\Components\TextInput::make(\$tree?->getLabelField() ?? 'name')->required(),
                        ])
                        ->fillForm(fn (\$record): array => is_array(\$record) ? \$record : \$record->toArray())
                        ->action(function (array \$data, \$record, array \$arguments) use (\$tree): void {
                            // \$record is the Eloquent model or the array node
                            // \$arguments['nodeId'] is the node's primary key
                            \Filament\Notifications\Notification::make()
                                ->title('Rename Action Triggered')
                                ->body(json_encode([
                                    'data' => \$data,
                                    'record' => is_object(\$record) ? \$record->toArray() : \$record,
                                    'arguments' => \$arguments,
                                ]))
                                ->info()
                                ->send();
                            // Implement your rename logic here
                            // For example, if using static nodes, you would update the corresponding node in the static property
                            // If using a model, you would find the model instance and update it
                        })
                        ->after(fn (\$livewire) => \$livewire->dispatch('tree-refresh')),
            PHP);
        }

        return (string) (new Literal($body, $args));
    }
}
