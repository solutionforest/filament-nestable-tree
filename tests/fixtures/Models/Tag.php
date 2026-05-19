<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class Tag extends Model
{
    use NodeTrait;

    protected $table = 'tags';

    protected $guarded = [];
}
