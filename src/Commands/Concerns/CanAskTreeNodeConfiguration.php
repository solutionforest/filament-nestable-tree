<?php

namespace SolutionForest\FilamentNestableTree\Commands\Concerns;

use Illuminate\Database\Eloquent\Model;

use function Filament\Support\discover_app_classes;
use function Laravel\Prompts\confirm;
use function Laravel\Prompts\text;
use function Laravel\Prompts\suggest;

trait CanAskTreeNodeConfiguration
{
    protected ?string $treeModelFqn = null;

    protected ?string $treeStaticNodePropertyName = null;

    protected array $multipleTreeKeys = [];

    protected function askTreeNodeConfigurationForWidget(): void
    {
        $treeUseStaticNodes = confirm(
            label: 'Will this tree use static nodes (i.e. not backed by a database model)?',
            default: false,
        );

        if ($treeUseStaticNodes) {
            
            $this->treeStaticNodePropertyName = text(
                label: 'What is the name of the property that will hold the nodes for the tree? ',
                placeholder: 'e.g. nodes',
                required: true,
                default: 'nodes',
                validate: function ($value) {
                    if (str_contains($value, ' ')) {
                        return 'The property name cannot contain spaces.';
                    }

                    return null;
                }
            );
        } else {
            

                $modelFqns = discover_app_classes(parentClass: Model::class);

                $this->treeModelFqn = suggest(
                    label: 'What is the model?',
                    options: function (string $search) use ($modelFqns): array {
                        $search = str($search)->trim()->replace(['\\', '/'], '');

                        if (blank($search)) {
                            return $modelFqns;
                        }

                        return array_filter(
                            $modelFqns,
                            fn (string $class): bool => str($class)->replace(['\\', '/'], '')->contains($search, ignoreCase: true),
                        );
                    },
                    placeholder: app()->getNamespace() . 'Models\\BlogPost',
                    required: true,
                    validate: function ($value) {
                        $checkValue = (string) $value;
                        $trimmedValue = (string) str($checkValue)->trim();
                        if (empty($trimmedValue)) {
                            return 'The model class name is required.';
                        }
                        return null;
                    }
                );
        }
    }

    protected function askTreeNodeConfigurationForPage(bool $hasResource) : void
    {
        $treeUseStaticNodes = confirm(
            label: 'Will this tree use static nodes (i.e. not backed by a database model)?',
            default: ! $hasResource,
        );

        if ($treeUseStaticNodes) {
            
            $this->treeStaticNodePropertyName = text(
                label: 'What is the name of the property that will hold the nodes for the tree? ',
                placeholder: 'e.g. nodes',
                required: true,
                default: 'nodes',
                validate: function ($value) {
                    if (str_contains($value, ' ')) {
                        return 'The property name cannot contain spaces.';
                    }

                    return null;
                }
            );
        } else {
            $useResourceModel = $hasResource
                ? confirm(
                    label: 'Do you want to use the model from the associated Filament Resource for the tree?',
                    default: true,
                )
                : false;

            if (! $useResourceModel) {

                $modelFqns = discover_app_classes(parentClass: Model::class);

                $this->treeModelFqn = suggest(
                    label: 'What is the model?',
                    options: function (string $search) use ($modelFqns): array {
                        $search = str($search)->trim()->replace(['\\', '/'], '');

                        if (blank($search)) {
                            return $modelFqns;
                        }

                        return array_filter(
                            $modelFqns,
                            fn (string $class): bool => str($class)->replace(['\\', '/'], '')->contains($search, ignoreCase: true),
                        );
                    },
                    placeholder: app()->getNamespace() . 'Models\\BlogPost',
                    required: true,
                    validate: function ($value) {
                        $checkValue = (string) $value;
                        $trimmedValue = (string) str($checkValue)->trim();
                        if (empty($trimmedValue)) {
                            return 'The model class name is required.';
                        }
                        return null;
                    }
                );
            }
        }
        
        $isMultipleTrees = confirm(
            label: 'Will this page contain multiple trees?',
            default: false,
        );

        if ($isMultipleTrees) {
            
            $this->multipleTreeKeys = str(
                text(
                    label: 'Please provide unique keys for each tree, separated by commas. ',
                    placeholder: 'e.g. categories,tags',
                    validate: function ($value) {
                        $keys = array_map('trim', explode(',', $value));
                        foreach ($keys as $key) {
                            if (str_contains($key, ' ')) {
                                return 'Tree keys cannot contain spaces.';
                            }
                        }

                        return null;
                    }
                )
            )
                ->explode(',')
                ->map(fn ($key) => trim($key))
                ->filter()
                ->values()
                ->all();
        }
    }
}
