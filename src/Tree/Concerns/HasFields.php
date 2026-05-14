<?php

namespace SolutionForest\FilamentNestableTree\Tree\Concerns;

use Closure;

trait HasFields
{
    protected string | Closure $labelField = 'name';

    protected string | Closure $childrenField = 'children';

    protected string | Closure $parentKeyField = 'parent_id';

    protected string | Closure $recordKeyField = 'id';

    public function labelField(string | Closure $field): static
    {
        $this->labelField = $field;

        return $this;
    }

    public function childrenField(string | Closure $field): static
    {
        $this->childrenField = $field;

        return $this;
    }

    public function parentKeyField(string | Closure $field): static
    {
        $this->parentKeyField = $field;

        return $this;
    }

    public function recordKeyField(string | Closure $field): static
    {
        $this->recordKeyField = $field;

        return $this;
    }

    public function getLabelField(): string
    {
        $safe = $this->evaluate($this->labelField);

        $result = strval($safe);

        if (empty($result)) {
            $result = 'name';
        }

        return $result;
    }

    public function getChildrenField(): string
    {
        $safe = $this->evaluate($this->childrenField);

        $result = strval($safe);

        if (empty($result)) {
            $result = 'children';
        }

        return $result;
    }

    public function getParentKeyField(): string
    {
        $safe = $this->evaluate($this->parentKeyField);

        $result = strval($safe);

        if (empty($result)) {
            $result = 'parent_id';
        }

        return $result;
    }

    public function getRecordKeyField(): string
    {
        $safe = $this->evaluate($this->recordKeyField);

        $result = strval($safe);

        if (empty($result)) {
            $result = 'id';
        }

        return $result;
    }
}
