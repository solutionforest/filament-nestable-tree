<?php

namespace SolutionForest\FilamentNestableTree\Tree\Concerns;

use Closure;
use Filament\Notifications\Notification;
use Illuminate\Database\Eloquent\Model;
use SolutionForest\FilamentNestableTree\Exceptions\MissingSaveOrderCallbackException;

trait HasOptions
{
    protected int | Closure $maxDepth = -1;

    protected int | Closure $maxVisibleDepth = 4;

    protected bool | Closure $searchable = false;

    protected bool | Closure $draggable = true;

    protected bool | Closure $allowCrossCategory = true;

    protected bool | Closure $lazy = false;

    /**
     * When set, children are loaded on-demand (async) when a node is expanded
     * for the first time. The closure receives the parent node ID.
     *
     * @var Closure(int|string): array<int|string, mixed>|null
     */
    protected ?Closure $asyncChildrenUsing = null;

    /**
     * When true, nodes absent from the reorder payload are deleted from the
     * database when calling rebuildTree() on a NodeTrait model.
     * Mirrors the $delete parameter of Kalnoy\Nestedset rebuildTree().
     */
    protected bool | Closure $deleteRemovedNodes = false;

    /** @var Closure(array<int|string, mixed>): void|null */
    protected ?Closure $saveOrderUsing = null;

    public function maxDepth(int | Closure $depth): static
    {
        $this->maxDepth = $depth;

        return $this;
    }

    public function maxVisibleDepth(int | Closure $depth): static
    {
        $this->maxVisibleDepth = $depth;

        return $this;
    }

    public function searchable(bool | Closure $value = true): static
    {
        $this->searchable = $value;

        return $this;
    }

    public function draggable(bool | Closure $value = true): static
    {
        $this->draggable = $value;

        return $this;
    }

    public function allowCrossCategory(bool | Closure $value = true): static
    {
        $this->allowCrossCategory = $value;

        return $this;
    }

    /**
     * Whether nodes absent from the reorder payload should be deleted when the
     * model uses Kalnoy\Nestedset NodeTrait and rebuildTree() is called.
     */
    public function deleteRemovedNodes(bool | Closure $value = true): static
    {
        $this->deleteRemovedNodes = $value;

        return $this;
    }

    /**
     * Defer node loading until after the component has rendered.
     *
     * When lazy is enabled, the tree component renders immediately with an
     * empty list and then calls `loadNodes()` via Alpine `x-init`, which
     * triggers a Livewire request to fetch and populate the nodes.
     * Use this for large trees where a fast first paint is preferred.
     */
    public function lazy(bool | Closure $value = true): static
    {
        $this->lazy = $value;

        return $this;
    }

    /**
     * Register a callback to persist the new node order.
     * The closure receives the nested node array from Alpine entanglement.
     *
     * @param  Closure(array<int|string, mixed>): void  $callback
     */
    public function saveOrderUsing(Closure $callback): static
    {
        $this->saveOrderUsing = $callback;

        return $this;
    }

    /**
     * Execute the save-order logic.
     *
     * Resolution order:
     *   1. User-supplied saveOrderUsing() closure — called with the nested nodes array.
     *   2. Model uses kalnoy/nestedset NodeTrait — rebuildTree() is called automatically.
     *   3. Neither — throws MissingSaveOrderCallbackException.
     *
     * @param  array<int|string, mixed>  $orderedNodes
     *
     * @throws MissingSaveOrderCallbackException
     */
    public function executeSaveOrder(array $orderedNodes): void
    {
        $savedNotification = Notification::make()
            // TODO: add 'Saved' translation key
            ->title(__('Saved'))
            ->success();

        if ($this->saveOrderUsing !== null) {
            $this->evaluate($this->saveOrderUsing, [
                'nodes' => $orderedNodes,
            ]);

            $savedNotification->send();

            return;
        }

        /** @var class-string<Model>|null */
        $modelClass = $this->getModel();

        if ($modelClass !== null) {
            $instance = new $modelClass;

            if (in_array('Kalnoy\Nestedset\NodeTrait', class_uses_recursive($instance))) {

                $modelClass::rebuildTree($orderedNodes, $this->deleteRemovedNodes);

                $savedNotification->send();

                return;
            }
        }

        throw new MissingSaveOrderCallbackException(
            modelClass: $modelClass,
        );
    }

    public function hasSaveOrderCallback(): bool
    {
        return $this->saveOrderUsing !== null;
    }

    public function getMaxDepth(): int
    {
        $safe = $this->evaluate($this->maxDepth);
        if ($safe !== null) {
            return $safe;
        }

        throw new \LogicException('Max depth must be defined as an integer or Closure that returns an integer.');
    }

    public function getMaxVisibleDepth(): int
    {
        $safe = $this->evaluate($this->maxVisibleDepth);
        if ($safe !== null) {
            return $safe;
        }

        throw new \LogicException('Max visible depth must be defined as an integer or Closure that returns an integer.');
    }

    public function isSearchable(): bool
    {
        return boolval($this->evaluate($this->searchable));
    }

    public function isDraggable(): bool
    {
        return boolval($this->evaluate($this->draggable));
    }

    public function isCrossCategoryAllowed(): bool
    {
        return boolval($this->evaluate($this->allowCrossCategory));
    }

    public function isLazy(): bool
    {
        return boolval($this->evaluate($this->lazy));
    }

    public function shouldDeleteRemovedNodes(): bool
    {
        return boolval($this->evaluate($this->deleteRemovedNodes));
    }

    // ── Async children loading ────────────────────────────────────────────────

    /**
     * When set, children for a given node are loaded lazily on first expand
     * rather than being included in the initial tree payload.
     *
     * The closure receives `int|string $parentId` and must return an array of
     * child nodes (same structure as `getNodes()` returns, but one level deep).
     *
     * @param  Closure(int|string): array<int|string, mixed>  $callback
     */
    public function asyncChildren(?Closure $callback = null): static
    {
        $this->asyncChildrenUsing = $callback;

        return $this;
    }

    /**
     * Load the immediate children of the given parent node.
     *
     * @return array<int|string, mixed>
     */
    public function getChildren(int | string $parentId): array
    {
        if ($this->asyncChildrenUsing !== null) {
            $result = $this->evaluate($this->asyncChildrenUsing, [
                'parentId' => $parentId,
                'id' => $parentId,
            ]);

            return is_array($result) ? $result : [];
        }

        // Fallback: load from model using the parent key field.
        if ($this->model !== null) {
            /** @var class-string<Model> $model */
            $model = $this->model;
            $parentField = $this->getParentKeyField();

            return $model::where($parentField, $parentId)
                ->get()
                ->toArray();
        }

        return [];
    }

    public function hasAsyncChildren(): bool
    {
        return $this->asyncChildrenUsing !== null;
    }
}
