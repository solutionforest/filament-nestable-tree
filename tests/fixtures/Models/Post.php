<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * A plain Eloquent model without NodeTrait.
 * Uses a simple parent_id / order structure.
 *
 * @property int $id
 * @property string $name
 * @property int $order
 * @property int|null $parent_id
 */
class Post extends Model
{
    protected $table = 'posts';

    protected $guarded = [];

    public function children(): HasMany
    {
        return $this->hasMany(Post::class, 'parent_id')->orderBy('order')->with('children');
    }
}
