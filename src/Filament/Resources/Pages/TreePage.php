<?php

namespace SolutionForest\FilamentNestableTree\Filament\Resources\Pages;

use Closure;
use Filament\Actions\Action;
use Filament\Actions\CreateAction;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Actions\ViewAction;
use Filament\Facades\Filament;
use Filament\Resources\Pages\Page;
use Filament\Schemas\Components\View;
use Filament\Schemas\Schema;
use SolutionForest\FilamentNestableTree\Concerns\InteractsWithTree;
use SolutionForest\FilamentNestableTree\Tree;

abstract class TreePage extends Page
{
    use InteractsWithTree;

    public function mount(): void
    {
        $this->mountTree();
    }

    public function getMountedAction(?int $actionNestingIndex = null): ?Action
    {
        $action = parent::getMountedAction($actionNestingIndex);
        $this->injectNodeRecordIntoAction($action);

        return $action;
    }

    public function form(Schema $schema): Schema
    {
        return static::getResource()::form($schema);
    }

    public function infolist(Schema $schema): Schema
    {
        return static::getResource()::infolist($schema);
    }

    public function getDefaultActionSchemaResolver(Action $action): ?Closure
    {
        return match (true) {
            $action instanceof CreateAction, $action instanceof EditAction => fn (Schema $schema): Schema => $this->form($schema->hasCustomColumns() ? $schema : $schema->columns(2)),
            $action instanceof ViewAction => fn (Schema $schema): Schema => $this->infolist($this->form($schema->hasCustomColumns() ? $schema : $schema->columns(2))),
            default => null,
        };
    }

    /**
     * Default tree configuration for a resource page: exposes Edit and Delete
     * node actions wired to the resource's form schema.
     */
    public function tree(Tree $tree): Tree
    {
        return $tree
            ->nodeActions([
                EditAction::make('edit_node')
                    ->label('Edit')
                    ->icon('heroicon-m-pencil-square')
                    ->color('gray')
                    ->iconButton()
                    ->fillForm(fn ($record) => $record?->toArray() ?? [])
                    ->schema(fn (Schema $schema) => $this->form($schema->hasCustomColumns() ? $schema : $schema->columns(2)))
                    ->after(fn () => $this->dispatch('tree-refresh')),

                DeleteAction::make('delete_node')
                    ->label('Delete')
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->iconButton()
                    ->after(fn () => $this->dispatch('tree-refresh')),
            ]);
    }

    protected function buildTree(): Tree
    {
        return Tree::make();
    }

    public function content(Schema $schema): Schema
    {
        $namedTrees = $this->trees();

        if (! empty($namedTrees)) {
            $components = [];

            foreach ($namedTrees as $key => $treeConfig) {
                $components[] = View::make('filament-nestable-tree::livewire.components.tree')
                    ->viewData($this->buildTreeViewData($treeConfig, 'namedTreeNodes.' . $key, (string) $key))
                    ->key((string) $key);
            }

            return $schema->components($components);
        }

        return $schema->components([
            View::make('filament-nestable-tree::livewire.components.tree')
                ->viewData($this->buildTreeViewData($this->getCachedTree(), 'treeNodes'))
                ->key('default'),
        ]);
    }

    /**
     * @return array<string, mixed>
     */
    private function buildTreeViewData(Tree $treeConfig, string $wireProperty, ?string $treeKeyName = null): array
    {
        return [
            'wireNodesProperty' => $wireProperty,
            'treeConfig' => $treeConfig,
            'treeKeyName' => $treeKeyName,
            'isSearchable' => $treeConfig->isSearchable(),
            'allowDragDrop' => $treeConfig->isDraggable(),
            'allowCrossCategory' => $treeConfig->isCrossCategoryAllowed(),
            'toolbarActions' => $treeConfig->getToolbarActions(),
            'lazy' => $treeConfig->isLazy(),
            'hasNodeActions' => ! empty($treeConfig->getNodeActions()),
        ];
    }

    /**
     * @return array<string>
     */
    public function getPageClasses(): array
    {
        return [
            'fi-resource-tree-page',
            'fi-resource-' . str_replace('/', '-', $this->getResource()::getSlug(Filament::getCurrentOrDefaultPanel())),
        ];
    }
}
