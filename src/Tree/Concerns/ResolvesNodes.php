<?php

namespace SolutionForest\FilamentNestableTree\Tree\Concerns;

use Closure;
use Illuminate\Database\Eloquent\Model;

trait ResolvesNodes
{
    protected string | Closure | null $model = null;

    protected array | Closure $records = [];

    protected ?Closure $getRecordUsing = null;

    public function model(string | Closure | null $model): static
    {
        $this->model = $model;

        return $this;
    }

    public function records(array | Closure $records): static
    {
        $this->records = $records;

        return $this;
    }

    public function getRecordUsing(?Closure $callback): static
    {
        $this->getRecordUsing = $callback;

        return $this;
    }

    public function getModel(): ?string
    {
        $safe = $this->evaluate($this->model);

        return ($safe !== null && $safe !== '') ? (string) $safe : null;
    }

    public function getRecords(): array
    {
        $safe = $this->evaluate($this->records);

        return is_array($safe) ? $safe : [];
    }

    /**
     * Load and return the tree data as a nested array.
     *
     * @return array<int|string, mixed>
     */
    public function getNodes(): array
    {
        if ($this->model !== null) {
            return $this->loadFromModel();
        }

        return $this->getRecords();
    }

    public function getNodeById(int | string $id): mixed
    {
        return $this->getNodeRecord($id);
    }

    /**
     * Resolve the node record for the given primary-key value.
     *
     * - When a model class is configured, returns the Eloquent Model instance.
     * - When static records are used, returns the matching array node (flat search).
     * - Returns null when no match is found.
     *
     * @return Model|array<string, mixed>|null
     */
    public function getNodeRecord(int | string $id): mixed
    {
        if ($this->getRecordUsing !== null) {
            return $this->evaluate($this->getRecordUsing, [
                'id' => $id,
            ]);
        }

        $keyField = $this->getRecordKeyField();

        if ($this->model !== null) {
            /** @var class-string<Model> $model */
            $model = $this->model;

            return $model::where($keyField, $id)->first();
        }

        $nodes = $this->getRecords();

        $flatten = $this->flattenNodes($nodes);

        return collect($flatten)->firstWhere($keyField, $id);
    }

    /**
     * @return array<int|string, mixed>
     */
    protected function loadFromModel(): array
    {
        /** @var class-string<Model> $model */
        $model = $this->model;

        $instance = new $model;

        // Detect kalnoy/nestedset NodeTrait presence.
        // Use defaultOrder()->get()->toTree() — the canonical way to load a
        // full nested-set tree. This fetches ALL nodes ordered by _lft so that
        // toTree()/linkNodes() groups them correctly AND siblings are returned
        // in their stored _lft order after every rebuildTree() call.
        if (in_array('Kalnoy\Nestedset\NodeTrait', class_uses_recursive($instance))) {
            return $model::defaultOrder()->get()->toTree()->toArray();
        }

        // Fallback: load all records, let the caller structure them
        return $model::whereNull($this->parentKeyField)
            ->with($this->childrenField)
            ->get()
            ->toArray();
    }

    protected function flattenNodes(array $nodes): array
    {
        $flattened = [];

        foreach ($nodes as $node) {
            $flattened[] = $node;

            if (isset($node[$this->childrenField]) && is_array($node[$this->childrenField])) {
                $flattened = array_merge($flattened, $this->flattenNodes($node[$this->childrenField]));
            }
        }

        return $flattened;
    }
}
