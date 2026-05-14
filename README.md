# Filament Nestable Tree

[![Latest Version on Packagist](https://img.shields.io/packagist/v/solutionforest/filament-nestable-tree.svg?style=flat-square)](https://packagist.org/packages/solutionforest/filament-nestable-tree)
[![GitHub Tests Action Status](https://img.shields.io/github/actions/workflow/status/solutionforest/filament-nestable-tree/run-tests.yml?branch=main&label=tests&style=flat-square)](https://github.com/solutionforest/filament-nestable-tree/actions?query=workflow%3Arun-tests+branch%3Amain)
[![GitHub Code Style Action Status](https://img.shields.io/github/actions/workflow/status/solutionforest/filament-nestable-tree/fix-php-code-style-issues.yml?branch=main&label=code%20style&style=flat-square)](https://github.com/solutionforest/filament-nestable-tree/actions?query=workflow%3A"Fix+PHP+code+styling"+branch%3Amain)
[![Total Downloads](https://img.shields.io/packagist/dt/solutionforest/filament-nestable-tree.svg?style=flat-square)](https://packagist.org/packages/solutionforest/filament-nestable-tree)

A nestable drag-and-drop tree component for [Filament](https://filamentphp.com) v5. Supports Eloquent models (including [`kalnoy/nestedset`](https://github.com/lazychaser/laravel-nestedset)), static record arrays, per-node actions, multi-tree pages, cross-tree drag-and-drop, lazy loading, and async child loading.

> **Example usage** — see the [demo application](https://github.com/solutionforest/filament-nestable-tree-dev) and the ready-to-run [fixture pages](tests/fixtures/Pages) in this repository.

---

## Installation

```bash
composer require solutionforest/filament-nestable-tree
```

> [!IMPORTANT]
> If you are using Filament Panels with a custom theme, add the plugin's views to your theme CSS file so Tailwind can scan them:
>
> ```css
> @source '../../../../vendor/solutionforest/filament-nestable-tree/resources/**/*.blade.php';
> ```
>
> If you have not yet set up a custom theme, follow the [Filament theming guide](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) first.

Optionally publish the stub files:

```bash
php artisan vendor:publish --tag="filament-nestable-tree-stubs"
```

---

## Quick Start

### 1 — Standalone Tree Page

Create a Filament page that shows a tree:

```bash
php artisan make:filament-tree-page CategoryTreePage
```

```php
use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tree;

class CategoryTreePage extends TreePage
{
    protected static ?string $navigationLabel = 'Categories';

    public function tree(Tree $tree): Tree
    {
        return $tree->model(Category::class)->labelField('title');
    }
}
```

### 2 — Resource Tree Page (replaces ListRecords)

```bash
php artisan make:filament-tree-resource-page ManageCategoryTree
```

```php
use SolutionForest\FilamentNestableTree\Filament\Resources\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tree;

class ManageCategoryTree extends TreePage
{
    public static string $resource = CategoryResource::class;

    public function tree(Tree $tree): Tree
    {
        return parent::tree($tree)   // includes default EditAction + DeleteAction
            ->model(Category::class)
            ->labelField('title');
    }
}
```

Register the page in your resource's `getPages()`:

```php
public static function getPages(): array
{
    return [
        'index' => ManageCategoryTree::route('/'),
    ];
}
```

### 3 — Tree Widget

```bash
php artisan make:filament-tree-widget CategoryTreeWidget
```

```php
use SolutionForest\FilamentNestableTree\Filament\Widgets\Tree as TreeWidget;
use SolutionForest\FilamentNestableTree\Tree;

class CategoryTreeWidget extends TreeWidget
{
    public function tree(Tree $tree): Tree
    {
        return $tree->model(Category::class)->labelField('title');
    }
}
```

### 4 — Embed in Any Livewire Component (InteractsWithTree)

Add the `InteractsWithTree` trait to any Livewire component (including custom pages, widgets, or plain Livewire components) to embed a tree without extending a base class:

```php
use Livewire\Component;
use SolutionForest\FilamentNestableTree\Concerns\InteractsWithTree;
use SolutionForest\FilamentNestableTree\Tree;

class MyCustomPage extends Component
{
    use InteractsWithTree;

    public function tree(Tree $tree): Tree
    {
        return $tree->model(Category::class)->labelField('title');
    }
}
```

Then render the tree in your Blade view:

```blade
@include('filament-nestable-tree::livewire.components.tree', [
    'wireNodesProperty'  => 'treeNodes',
    'treeKeyName'        => null,
    'treeConfig'         => $this->getCachedTree(),
    'isSearchable'       => $this->getCachedTree()->isSearchable(),
    'allowDragDrop'      => $this->getCachedTree()->isDraggable(),
    'allowCrossCategory' => $this->getCachedTree()->isCrossCategoryAllowed(),
    'toolbarActions'     => $this->getCachedTree()->getToolbarActions(),
    'lazy'               => $this->getCachedTree()->isLazy(),
    'hasNodeActions'     => ! empty($this->getCachedTree()->getNodeActions()),
])
```

> Livewire automatically calls `mountInteractsWithTree()` after your component's `mount()` to populate the tree nodes — no manual setup required.

---

## Tree Configuration Reference

All options are fluent methods on the `Tree` instance returned from `tree()` or `trees()`.

| Method                          | Default          | Description                                                 |
| ------------------------------- | ---------------- | ----------------------------------------------------------- |
| `->model(Category::class)`      | `null`           | Eloquent model to load the tree from                        |
| `->records([...])`              | `[]`             | Static nested/flat array (alternative to `model`)           |
| `->labelField('name')`          | `'name'`         | Attribute used as the display label                         |
| `->recordKeyField('id')`        | `'id'`           | Attribute used as the unique identifier                     |
| `->parentKeyField('parent_id')` | `'parent_id'`    | Attribute used as the parent reference                      |
| `->childrenField('children')`   | `'children'`     | Attribute that holds nested children                        |
| `->maxDepth(3)`                 | `-1` (unlimited) | Maximum nesting depth for drag-and-drop                     |
| `->maxVisibleDepth(5)`          | `4`              | Maximum rendered depth in the flat list view                |
| `->searchable()`                | `false`          | Show the search input and highlight matching labels         |
| `->draggable(false)`            | `true`           | Enable or disable drag-and-drop reordering                  |
| `->allowCrossCategory()`        | `false`          | Allow nodes to move between root-level branches             |
| `->lazy()`                      | `false`          | Defer node loading until after first render                 |
| `->asyncChildren(fn)`           | `null`           | Load children on-demand when a node is expanded             |
| `->saveOrderUsing(fn)`          | `null`           | Closure to persist reorder; receives the nested nodes array |
| `->getRecordUsing(fn)`          | `null`           | Custom closure to resolve a node record by its ID           |
| `->nodeActions([...])`          | `[]`             | Per-node action buttons (edit, delete, custom)              |
| `->appendToolbarActions([...])` | —                | Add buttons to the toolbar (append to defaults)             |

---

## Saving Order After Drag & Drop

### Option 1 — Automatic (kalnoy/nestedset)

If your model uses the [`kalnoy/nestedset`](https://github.com/lazychaser/laravel-nestedset) `NodeTrait`, the tree calls `rebuildTree()` automatically when the Save button is clicked — no extra configuration required:

```php
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait;
}
```

```php
public function tree(Tree $tree): Tree
{
    return $tree->model(Category::class)->labelField('title');
}
```

### Option 2 — Custom callback

```php
public function tree(Tree $tree): Tree
{
    return $tree
        ->model(Category::class)
        ->saveOrderUsing(function (array $nodes): void {
            // $nodes is the full nested array from Alpine
            foreach ($nodes as $index => $node) {
                Category::where('id', $node['id'])->update(['sort_order' => $index]);
            }
        });
}
```

If neither option is configured, a `MissingSaveOrderCallbackException` is thrown at runtime when save is triggered.

---

## Node Actions

Add per-node action buttons:

```php
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

public function tree(Tree $tree): Tree
{
    return $tree
        ->model(Category::class)
        ->nodeActions([
            EditAction::make()->iconButton()->size('sm'),
            DeleteAction::make()->iconButton()->size('sm')->color('danger'),
        ]);
}
```

### Custom node actions

```php
use Filament\Actions\Action;
use Filament\Forms\Components\TextInput;
use Filament\Schemas\Schema;

->nodeActions(fn (Tree $tree) => [
    Action::make('rename')
        ->iconButton()
        ->icon('heroicon-o-pencil')
        ->schema([TextInput::make('title')->required()])
        ->fillForm(fn ($record): array => is_array($record) ? $record : $record->toArray())
        ->action(function (array $data, $record, array $arguments) use ($tree): void {
            // $record is the Eloquent model or the array node
            // $arguments['nodeId'] is the node's primary key
            $record->update(['title' => $data['title']]);
        })
        ->after(fn ($livewire) => $livewire->dispatch('tree-refresh')),
])
```

### `getRecordUsing` — custom record resolution

By default, when a node action fires the plugin resolves the record from the database (for model-based trees) or the flat `records()` array (for static trees). Override this for custom lookups:

```php
->getRecordUsing(function (int|string $id, Tree $tree, $livewire): mixed {
    return Category::withTrashed()->find($id);
})
```

---

## Toolbar Actions

```php
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;
use Filament\Actions\CreateAction;

public function tree(Tree $tree): Tree
{
    return $tree
        ->model(Category::class)
        ->appendToolbarActions([
            CreateAction::make('create_node')
                ->model(Category::class)
                ->schema(fn (Schema $schema) => $this->form($schema))
                ->after(fn ($livewire) => $livewire->dispatch('tree-refresh'))
                ->extraAttributes(['style' => 'margin-left: auto;']),
        ]);
}
```

---

## Multiple Trees on One Page

Override `trees()` instead of `tree()` to render multiple independent trees:

```php
use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tree;

class MultiTreePage extends TreePage
{
    public function trees(): array
    {
        return [
            'categories' => Tree::make()->model(Category::class)->searchable()->labelField('title'),
            'tags'       => Tree::make()->model(Tag::class)->labelField('name'),
        ];
    }
}
```

### Cross-tree drag & drop

To allow nodes to be dragged from one named tree to another, enable `->allowCrossCategory()` and handle the `tree-cross-move` event:

```php
class MultiTreePage extends TreePage
{
    public function trees(): array
    {
        return [
            'categories' => Tree::make()->model(Category::class)->allowCrossCategory(),
            'tags'       => Tree::make()->model(Tag::class)->allowCrossCategory(),
        ];
    }

    /**
     * Called automatically when a node is dragged from one tree to another.
     */
    public function handleCrossTreeMove(
        string $fromTreeKey,
        string $toTreeKey,
        int|string $nodeId,
        mixed $destinationParentId = null,
    ): void {
        $node = Category::find($nodeId);
        $node->update(['parent_id' => $destinationParentId]);
        $this->dispatch('tree-refresh');
    }
}
```

---

## Async / Lazy Loading

### `->lazy()` — defer initial load

Renders the component shell immediately and loads nodes in a second Livewire request. Useful for large trees:

```php
Tree::make()->model(Category::class)->lazy()
```

### `->asyncChildren()` — expand-on-demand

Load children only when a node is expanded for the first time. The closure receives the parent node's ID:

```php
Tree::make()
    ->model(Category::class)
    ->asyncChildren(function (int|string $parentId): array {
        return Category::where('parent_id', $parentId)->get()->toArray();
    })
```

When async children are enabled, the root-level nodes are loaded normally on mount. Child nodes are fetched via a Livewire call when the user expands a parent for the first time, and cached client-side for subsequent toggles.

---

## Artisan Generators

```bash
# Standalone Filament page
php artisan make:filament-tree-page CategoryTreePage

# Resource page (replaces ListRecords)
php artisan make:filament-tree-resource-page ManageCategoryTree

# Widget
php artisan make:filament-tree-widget CategoryTreeWidget
```

---

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [carly](https://github.com/cklei-carly)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.

## Installation

You can install the package via composer:

```bash
composer require solutionforest/filament-nestable-tree
```

> [!IMPORTANT]
> If you have not set up a custom theme and are using Filament Panels follow the instructions in the [Filament Docs](https://filamentphp.com/docs/4.x/styling/overview#creating-a-custom-theme) first.

After setting up a custom theme add the plugin's views to your theme css file or your app's css file if using the standalone packages.

```css
@source '../../../../vendor/solutionforest/filament-nestable-tree/resources/**/*.blade.php';
```

You can publish and run the migrations with:

```bash
php artisan vendor:publish --tag="filament-nestable-tree-migrations"
php artisan migrate
```

You can publish the config file with:

```bash
php artisan vendor:publish --tag="filament-nestable-tree-config"
```

Optionally, you can publish the views using

```bash
php artisan vendor:publish --tag="filament-nestable-tree-views"
```

This is the contents of the published config file:

```php
return [
];
```

## Usage

### Tree Configuration Reference

All options are fluent methods on the `Tree` instance returned from `tree()` or `trees()`.

| Method                          | Default          | Description                                                          |
| ------------------------------- | ---------------- | -------------------------------------------------------------------- |
| `->model(Category::class)`      | `null`           | Eloquent model class to load the tree from                           |
| `->records([...])`              | `[]`             | Static nested/flat array of nodes (alternative to `model`)           |
| `->labelField('name')`          | `'name'`         | Node attribute used as the display label                             |
| `->recordKeyField('id')`        | `'id'`           | Node attribute used as the unique identifier                         |
| `->parentKeyField('parent_id')` | `'parent_id'`    | Node attribute used as the parent reference                          |
| `->childrenField('children')`   | `'children'`     | Node attribute that holds nested children                            |
| `->maxDepth(3)`                 | `-1` (unlimited) | Maximum nesting depth for drag-and-drop (rejects deeper drops)       |
| `->maxVisibleDepth(5)`          | `4`              | Maximum rendered depth in the flattened view                         |
| `->searchable()`                | `false`          | Show the search input and highlight matching labels                  |
| `->draggable(false)`            | `true`           | Enable or disable drag-and-drop reordering                           |
| `->allowCrossCategory()`        | `false`          | Allow nodes to be moved between different root-level branches        |
| `->lazy()`                      | `false`          | Defer node loading until after first render (useful for large trees) |
| `->saveOrderUsing(fn)`          | `null`           | Closure to persist reorder; receives the current nested nodes array  |

#### `model` vs `records`

Use `->model()` to load a live Eloquent tree:

```php
Tree::make()->model(Category::class)
```

Use `->records()` for a static in-memory tree (no database):

```php
Tree::make()
    ->labelField('title')
    ->records([
        ['id' => 1, 'parent_id' => null, 'title' => 'Root'],
        ['id' => 2, 'parent_id' => 1,    'title' => 'Child', 'children' => [
            ['id' => 3, 'parent_id' => 2, 'title' => 'Grandchild'],
        ]],
    ])
```

Flat records (with `parent_id` only, no `children` key) are also supported when using `->model()` — the package will structure them automatically via Eloquent relationships.

#### `labelField`

Points to the attribute displayed as the node text. Defaults to `name`. Change it when your model uses a different column:

```php
Tree::make()->model(Category::class)->labelField('title')
```

#### `maxDepth` vs `maxVisibleDepth`

- `maxDepth` controls drag-and-drop: drops that would exceed this depth are blocked (use `-1` for unlimited).
- `maxVisibleDepth` controls rendering: children beyond this depth are hidden (collapsed) in the flat list view. Increase it for very deep trees.

```php
Tree::make()
    ->maxDepth(3)          // drag-drop limited to 3 levels
    ->maxVisibleDepth(10)  // show up to 10 levels when expanded
```

#### `searchable`

Adds a search input above the tree. Matching labels are highlighted; non-matching branches show a visual indicator when they contain matches.

```php
Tree::make()->model(Category::class)->searchable()
```

#### `lazy`

By default nodes are loaded synchronously during the Livewire `mount()` — the page does not render until nodes are ready. Enable `->lazy()` to render the component shell immediately and load nodes in a second Livewire request, improving perceived performance for large trees.

```php
Tree::make()->model(Category::class)->lazy()
```

---

### Tree Page (Filament Panel)

```php
use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tree;

class CategoryTreePage extends TreePage
{
    protected static ?string $navigationLabel = 'Categories';

    public function tree(Tree $tree): Tree
    {
        return $tree->model(Category::class);
    }
}
```

### Tree Resource Page

```php
use SolutionForest\FilamentNestableTree\Filament\Resources\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tree;

class ManageCategoryTree extends TreePage
{
    public static string $resource = CategoryResource::class;

    public function tree(Tree $tree): Tree
    {
        return $tree->model(Category::class);
    }
}
```

---

### Saving Order After Drag & Drop

When a user reorders nodes by drag and drop, the tree tracks `hasUnsavedOrder` in Alpine.js. Call `saveOrder()` to persist the changes.

**Option 1 — Automatic (kalnoy/nestedset)**

If your model uses the [`kalnoy/nestedset`](https://github.com/lazychaser/laravel-nestedset) `NodeTrait`, the tree will call `rebuildTree()` automatically — no configuration required:

```php
use Kalnoy\Nestedset\NodeTrait;

class Category extends Model
{
    use NodeTrait;
}
```

The built-in **Save** toolbar button becomes visible whenever there are unsaved changes and will trigger `saveOrder()` automatically.

**Option 2 — Custom callback**

For models without `NodeTrait`, register a `saveOrderUsing` closure on the tree config:

```php
public function tree(Tree $tree): Tree
{
    return $tree
        ->model(Category::class)
        ->saveOrderUsing(function (array $nodes): void {
            // $nodes is the full nested array from Alpine
            foreach ($nodes as $index => $node) {
                Category::where('id', $node['id'])->update(['sort_order' => $index]);
            }
        });
}
```

If neither is configured, a `MissingSaveOrderCallbackException` will be thrown at runtime when save is triggered, with a message explaining what to do.

**Save button in toolbar**

The default toolbar includes a **Save** button that is hidden until a drag-drop reorder occurs (`x-show="hasUnsavedOrder"`). You can also add your own:

```php
use Filament\Actions\Action;

public function tree(Tree $tree): Tree
{
    return $tree
        ->appendToolbarActions([
            Action::make('save_order')
                ->label('Save')
                ->icon('heroicon-o-check')
                ->extraAttributes(['x-show' => 'hasUnsavedOrder', 'x-cloak' => true])
                ->action('saveOrder'),
        ]);
}
```

---

### Multiple Trees on One Page

Override `trees()` (instead of `tree()`) to render multiple independent trees on the same page:

```php
use SolutionForest\FilamentNestableTree\Filament\Pages\TreePage;
use SolutionForest\FilamentNestableTree\Tree;

class MultiTreePage extends TreePage
{
    public function trees(): array
    {
        return [
            'categories' => Tree::make()->model(Category::class)->searchable(),
            'tags'       => Tree::make()->model(Tag::class),
        ];
    }
}
```

Each tree is rendered as a separate Livewire component with a unique key (`categories`, `tags`), so their state is fully isolated.

---

### Toolbar Actions & Action Groups

You can pass both `Action` and `ActionGroup` instances to the toolbar:

```php
use Filament\Actions\Action;
use Filament\Actions\ActionGroup;

public function tree(Tree $tree): Tree
{
    return $tree->appendToolbarActions([
        ActionGroup::make([
            Action::make('import')->label('Import'),
            Action::make('export')->label('Export'),
        ])->label('More'),
    ]);
}
```

---

### Cross-Category Drag & Drop

By default, nodes cannot be moved between top-level root nodes (categories). To allow this:

```php
public function tree(Tree $tree): Tree
{
    return $tree
        ->model(Category::class)
        ->allowCrossCategory();
}
```

When disabled (the default), dropping a node onto a different root branch cancels the move silently.

---

### Node Actions

Add per-node action buttons (edit, delete, custom):

```php
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;

public function tree(Tree $tree): Tree
{
    return $tree
        ->model(Category::class)
        ->nodeActions([
            EditAction::make()->iconButton(),
            DeleteAction::make()->iconButton()->color('danger'),
        ]);
}
```

## Testing

```bash
composer test
```

## Changelog

Please see [CHANGELOG](CHANGELOG.md) for more information on what has changed recently.

## Contributing

Please see [CONTRIBUTING](.github/CONTRIBUTING.md) for details.

## Security Vulnerabilities

Please review [our security policy](.github/SECURITY.md) on how to report security vulnerabilities.

## Credits

- [carly](https://github.com/cklei-carly)
- [All Contributors](../../contributors)

## License

The MIT License (MIT). Please see [License File](LICENSE.md) for more information.
