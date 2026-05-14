<?php

namespace SolutionForest\FilamentNestableTree\Exceptions;

use RuntimeException;

class MissingSaveOrderCallbackException extends RuntimeException
{
    public function __construct(?string $modelClass = null)
    {
        $hint = $modelClass
            ? "The model [{$modelClass}] does not use the kalnoy/nestedset `NodeTrait`."
            : 'No model is configured on this tree.';

        parent::__construct(
            "Cannot save tree order: no `saveOrderUsing` callback is registered and the model does not support automatic reordering. {$hint} " .
            'Either call ->saveOrderUsing(fn (array $nodes) => ...) on your Tree config, ' .
            'or ensure your Eloquent model uses the `Kalnoy\\Nestedset\\NodeTrait` trait so the order can be persisted automatically. ' .
            'See the package README for details.',
        );
    }
}
