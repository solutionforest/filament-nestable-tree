<?php

namespace SolutionForest\FilamentNestableTree\Tests\Fixtures\Pages;

use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tree;

/**
 * A Filament tree page backed by a JSON file on disk.
 *
 * Tests set $jsonFile to a temp path before each test and clean it up
 * in afterEach. This exercises JSON read / write storage end-to-end.
 */
class StaticRecordsTreePage extends TreePage
{
    protected static ?string $slug = 'static-tree';

    protected static bool $shouldRegisterNavigation = false;

    /** Absolute path to the JSON file — set by tests before each run. */
    public static string $jsonFile = '';

    // ── JSON helpers ──────────────────────────────────────────────────────────

    public static function defaultNodes(): array
    {
        return [
            ['id' => 1, 'title' => 'Root A', 'parent_id' => null],
            ['id' => 2, 'title' => 'Root B', 'parent_id' => null],
            ['id' => 3, 'title' => 'Child B1', 'parent_id' => 2],
        ];
    }

    public static function readNodes(): array
    {
        if (static::$jsonFile === '' || ! file_exists(static::$jsonFile)) {
            return static::defaultNodes();
        }

        return json_decode(file_get_contents(static::$jsonFile), true) ?? static::defaultNodes();
    }

    public static function writeNodes(array $flat): void
    {
        if (static::$jsonFile !== '') {
            file_put_contents(static::$jsonFile, json_encode($flat, JSON_PRETTY_PRINT));
        }
    }

    // ── Tree ──────────────────────────────────────────────────────────────────

    public function tree(Tree $tree): Tree
    {
        return $tree
            ->labelField('title')
            ->records(fn () => static::asTree(static::readNodes()))
            ->saveOrderUsing(function (array $nodes) {
                static::writeNodes(static::asFlatten($nodes));
            })
            ->searchable();
    }

    // ── Helpers ───────────────────────────────────────────────────────────────

    /**
     * @param  array<int, array<string, mixed>>  $flat
     * @return array<int, array<string, mixed>>
     */
    public static function asTree(array $flat): array
    {
        $map = [];

        foreach ($flat as $item) {
            $map[$item['id']] = $item + ['children' => []];
        }

        $tree = [];

        foreach ($map as $id => &$node) {
            if ($node['parent_id'] === null || ! isset($map[$node['parent_id']])) {
                $tree[] = &$node;
            } else {
                $map[$node['parent_id']]['children'][] = &$node;
            }
        }

        return $tree;
    }

    /**
     * @param  array<int, array<string, mixed>>  $nodes
     * @return array<int, array<string, mixed>>
     */
    public static function asFlatten(array $nodes, mixed $parentId = null): array
    {
        $flat = [];

        foreach ($nodes as $node) {
            $children = $node['children'] ?? [];
            unset($node['children']);
            $node['parent_id'] = $parentId;
            $flat[] = $node;

            if (! empty($children)) {
                $flat = array_merge($flat, static::asFlatten($children, $node['id']));
            }
        }

        return $flat;
    }
}
