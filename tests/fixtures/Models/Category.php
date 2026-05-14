<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait;

    protected $table = 'categories';

    protected $guarded = [];
}
