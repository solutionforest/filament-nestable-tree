<?php

namespace SolutionForest\FilamentNestableTree\Concerns;

use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Livewire\Attributes\On;
use SolutionForest\FilamentNestableTree\Tree as TreeConfig;

/**
 * Add this trait to any Livewire component (Page, Widget, custom component)
 * to embed a fully-featured nestable tree directly — no child component needed.
 *
 * Pattern mirrors Filament's InteractsWithTable / InteractsWithActions traits.
 *
 * Single tree usage (implement tree()):
 * ```php
 * class MyPage extends Page
 * {
 *     use InteractsWithTree;
 *
 *     public function tree(Tree $tree): Tree
 *     {
 *         return $tree->model(Category::class);
 *     }
 * }
 * ```
 *
 * Multiple trees (implement trees()):
 * ```php
 * public function trees(): array
 * {
 *     return [
 *         'categories' => Tree::make()->model(Category::class),
 *         'tags'       => Tree::make()->model(Tag::class),
 *     ];
 * }
 * ```
 */
trait InteractsWithTree
{
    // ── Runtime cache — not persisted across Livewire requests ────────────────

    protected ?TreeConfig $cachedTree = null;

    /** @var array<string, TreeConfig> */
    protected array $cachedTrees = [];

    /**
     * Guards against mountTree() being called twice when both the component's
     * own mount() and the auto-called mountInteractsWithTree() hook run.
     */
    protected bool $treeMounted = false;

    // ── Livewire public state ─────────────────────────────────────────────────

    /**
     * Nested tree nodes for the *primary* tree, entangled with Alpine.
     * When multiple trees are used, each named tree's nodes are stored in
     * $namedTreeNodes keyed by the tree key.
     *
     * @var array<int|string, mixed>
     */
    public array $treeNodes = [];

    /**
     * Node arrays for each named tree when trees() is used.
     *
     * @var array<string, array<int|string, mixed>>
     */
    public array $namedTreeNodes = [];

    // ── Boot ──────────────────────────────────────────────────────────────────

    public function bootInteractsWithTree(): void
    {
        $this->cacheTreeActions();
    }

    /**
     * Register all node and toolbar actions into Filament's action cache
     * so they survive Livewire hydration cycles.
     */
    protected function cacheTreeActions(): void
    {
        foreach ($this->resolveAllTreeConfigs() as $treeConfig) {
            $this->cacheActionsFromTree($treeConfig);
        }
    }

    /**
     * @param  array<Action|ActionGroup>  $actions
     */
    private function cacheActionsFromList(array $actions): void
    {
        foreach ($actions as $action) {
            if ($action instanceof Action) {
                $this->cacheAction($action);
            } elseif ($action instanceof ActionGroup) {
                foreach ($action->getFlatActions() as $subAction) {
                    if ($subAction instanceof Action) {
                        $this->cacheAction($subAction);
                    }
                }
            }
        }
    }

    private function cacheActionsFromTree(TreeConfig $treeConfig): void
    {
        $this->cacheActionsFromList($treeConfig->getNodeActions());
        $this->cacheActionsFromList($treeConfig->getToolbarActions());
    }

    // ── Mount helper ──────────────────────────────────────────────────────────

    /**
     * Call this inside your component's mount() to load the initial nodes.
     * Handles both single-tree and multi-tree setups automatically.
     *
     * Idempotent — safe to call more than once; subsequent calls are no-ops.
     */
    public function mountTree(): void
    {
        if ($this->treeMounted) {
            return;
        }

        $this->treeMounted = true;

        if (! empty($this->trees())) {
            foreach ($this->trees() as $key => $treeConfig) {
                $this->namedTreeNodes[(string) $key] = $treeConfig->isLazy()
                    ? []
                    : $treeConfig->getNodes();
            }
        } else {
            $config = $this->getCachedTree();
            if (! $config->isLazy()) {
                $this->treeNodes = $config->getNodes();
            }
        }
    }

    /**
     * Livewire auto-calls mount{TraitName}() hooks after the component's own
     * mount() has finished. This ensures tree nodes are always loaded even
     * when the host component overrides mount() without calling parent::mount()
     * or $this->mountTree() explicitly.
     */
    public function mountInteractsWithTree(): void
    {
        $this->mountTree();
    }

    // ── Tree configuration API ────────────────────────────────────────────────

    /**
     * Override to configure the primary tree.
     */
    public function tree(TreeConfig $tree): TreeConfig
    {
        return $tree;
    }

    /**
     * Override to define multiple named trees.
     *
     * @return array<string, TreeConfig>
     */
    public function trees(): array
    {
        return [];
    }

    // ── Cached tree resolution ────────────────────────────────────────────────

    public function getCachedTree(): TreeConfig
    {
        if ($this->cachedTree === null) {
            $this->cachedTree = $this->tree(TreeConfig::make());
        }

        return $this->cachedTree;
    }

    public function getCachedTreeByKey(string $key): TreeConfig
    {
        if (! isset($this->cachedTrees[$key])) {
            $namedTrees = $this->trees();

            if (array_key_exists($key, $namedTrees)) {
                $this->cachedTrees[$key] = $namedTrees[$key];
            } else {
                $this->cachedTrees[$key] = TreeConfig::make();
            }
        }

        return $this->cachedTrees[$key];
    }

    /**
     * @return array<string, TreeConfig>
     */
    protected function resolveAllTreeConfigs(): array
    {
        $namedTrees = $this->trees();

        if (! empty($namedTrees)) {
            return $namedTrees;
        }

        return ['default' => $this->getCachedTree()];
    }

    // ── Events ────────────────────────────────────────────────────────────────

    /**
     * Reload nodes for all trees (or just the primary tree).
     * Dispatch `tree-refresh` from anywhere to trigger this.
     */
    #[On('tree-refresh')]
    public function refreshTreeNodes(): void
    {
        $this->cachedTree = null;
        $this->cachedTrees = [];

        if (! empty($this->trees())) {
            foreach ($this->trees() as $key => $treeConfig) {
                $this->namedTreeNodes[(string) $key] = $treeConfig->getNodes();
            }
        } else {
            $this->treeNodes = $this->getCachedTree()->getNodes();
        }
    }

    /**
     * Load nodes on demand (used when lazy = true).
     * Called from Alpine x-init after the first render.
     */
    public function loadTreeNodes(?string $treeKey = null): void
    {
        if ($treeKey !== null) {
            $this->namedTreeNodes[$treeKey] = $this->getCachedTreeByKey($treeKey)->getNodes();
        } else {
            $this->treeNodes = $this->getCachedTree()->getNodes();
        }
    }

    // ── Save / Reset order ────────────────────────────────────────────────────

    /**
     * Persist the current node order.
     *
     * @param  array<int|string, mixed>  $orderedNodes
     */
    public function saveTreeOrder(array $orderedNodes = [], ?string $treeKey = null): void
    {
        if ($treeKey !== null) {
            $config = $this->getCachedTreeByKey($treeKey);

            if (empty($orderedNodes)) {
                $orderedNodes = $this->namedTreeNodes[$treeKey] ?? [];
            }
        } else {
            $config = $this->getCachedTree();

            if (empty($orderedNodes)) {
                $orderedNodes = $this->treeNodes;
            }
        }

        $config->executeSaveOrder($orderedNodes);

        $this->dispatch('tree-order-saved', treeKey: $treeKey);
    }

    /**
     * Discard unsaved reordering and reload from the data source.
     */
    public function resetTreeOrder(?string $treeKey = null): void
    {
        if ($treeKey !== null) {
            $this->cachedTrees = array_filter(
                $this->cachedTrees,
                fn ($k) => $k !== $treeKey,
                ARRAY_FILTER_USE_KEY,
            );
            $this->namedTreeNodes[$treeKey] = $this->getCachedTreeByKey($treeKey)->getNodes();
        } else {
            $this->cachedTree = null;
            $this->treeNodes = $this->getCachedTree()->getNodes();
        }

        $this->dispatch('tree-reset', treeKey: $treeKey);
    }

    // ── Node actions ──────────────────────────────────────────────────────────

    /**
     * Inject the Eloquent record (or array record) into a mounted node action.
     *
     * Call this from your component's getMountedAction() override when the
     * action arguments contain `tree: true` (set by loadTreeNodeActions()).
     * This mirrors the logic in Livewire/Components/Tree::getMountedAction().
     */
    protected function injectNodeRecordIntoAction(?Action $action, ?string $treeKey = null): void
    {
        if ($action === null) {
            return;
        }

        $actionArgs = $action->getArguments();

        if (($actionArgs['tree'] ?? false) !== true) {
            return;
        }

        $nodeId = $actionArgs['nodeId'] ?? null;

        if ($nodeId === null) {
            return;
        }

        $treeConfig = $treeKey !== null
            ? $this->getCachedTreeByKey($treeKey)
            : $this->getCachedTree();

        $record = $treeConfig->getNodeRecord($nodeId);

        if ($record !== null && method_exists($action, 'record')) {
            $action->record($record);
        }
    }

    /**
     * Return HTML strings for the visible node actions for a given node.
     * Called from Alpine via $wire.loadTreeNodeActions(nodeId, treeKey).
     *
     * @return array<string>
     */
    public function loadTreeNodeActions(int | string $nodeId, ?string $treeKey = null): array
    {
        $treeConfig = $treeKey !== null
            ? $this->getCachedTreeByKey($treeKey)
            : $this->getCachedTree();

        $nodeRecord = $treeConfig->getNodeRecord($nodeId);
        $nodeActions = $treeConfig->getNodeActions();

        $visibleActions = array_filter(
            $nodeActions,
            function (Action | ActionGroup $action) use ($nodeRecord) {
                if (method_exists($action, 'record')) {
                    $action->record($nodeRecord);
                }

                return $action->isVisible();
            },
        );

        $recordKey = $nodeRecord[$treeConfig->getRecordKeyField()] ?? null;
        $arguments = ['tree' => true, 'recordKey' => $recordKey, 'nodeId' => $nodeId, 'treeKey' => $treeKey];

        return array_values(array_map(function (Action | ActionGroup $action) use ($arguments) {
            if ($action instanceof ActionGroup) {
                $invokedChildren = array_map(
                    fn (Action $child): Action => $child($arguments),
                    $action->getActions(),
                );

                $action = clone $action;
                $action->actions($invokedChildren);
            } else {
                $action = $action($arguments);
            }

            return $action->toHtml();
        }, $visibleActions));
    }

    /**
     * Async-children loader: returns the immediate children of the given node.
     *
     * Called from Alpine via `await $wire.call('loadChildren', nodeId, treeKey)`
     * when `asyncChildren` mode is enabled on the Tree config.
     *
     * @return array<int|string, mixed>
     */
    public function loadChildren(int | string $nodeId, ?string $treeKey = null): array
    {
        $treeConfig = $treeKey !== null
            ? $this->getCachedTreeByKey($treeKey)
            : $this->getCachedTree();

        return $treeConfig->getChildren($nodeId);
    }
}
