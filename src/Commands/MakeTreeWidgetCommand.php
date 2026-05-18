<?php

namespace SolutionForest\FilamentNestableTree\Commands;

use Filament\Support\Commands\Concerns\CanAskForLivewireComponentLocation;
use Filament\Support\Commands\Concerns\CanAskForResource;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Filament\Support\Commands\Concerns\HasCluster;
use Filament\Support\Commands\Concerns\HasPanel;
use Filament\Support\Commands\Concerns\HasResourcesLocation;
use Filament\Support\Commands\Exceptions\FailureCommandOutput;
use Filament\Support\Commands\FileGenerators\Concerns\CanCheckFileGenerationFlags;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use ReflectionClass;
use SolutionForest\FilamentNestableTree\Commands\Concerns\CanAskTreeNodeConfiguration;
use SolutionForest\FilamentNestableTree\Commands\FileGenerators\TreeWidgetClassGenerator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

class MakeTreeWidgetCommand extends Command
{
    use CanAskForLivewireComponentLocation;
    use CanAskForResource;
    use CanAskTreeNodeConfiguration;
    use CanCheckFileGenerationFlags;
    use CanManipulateFiles;
    use HasCluster;
    use HasPanel;
    use HasResourcesLocation;

    public $signature = 'make:filament-tree-widget
                        {name : The name of the widget class}
                        {--C|cluster= : The cluster to create the widget in (optional)}
                        {--panel= : The panel to create the widget in (optional)}
                        {--R|resource= : The resource to create the widget in (optional)}
                        {--resource-namespace= : The namespace of the resource class (optional), such as [App\\Filament\\Resources]}
                        {--force : Overwrite the file if it already exists}';

    public $description = 'Create a new Filament tree widget';

    protected $aliases = ['filament:make-tree-widget', 'filament:tree-widget'];

    /**
     * The fully qualified class name of the widget to create, such as [App\Filament\Widgets\CategoryTree].
     *
     * @var class-string
     */
    protected string $fqn;

    /**
     * The class name ending of the widget, such as [CategoryTree] for a widget with the FQN of [App\Filament\Widgets\CategoryTree].
     */
    protected string $fqnEnd;

    /**
     * Whether or not this widget is being created within a filament resource.
     */
    protected bool $hasResource = false;

    // ── Configuration properties ────────────────────────────────────────────────

    /**
     * The namespace to create the widget in, such as [App\Filament\Resources\Categories] for a widget with the FQN of [App\Filament\Resources\Categories\CategoryTree].
     */
    protected string $widgetsNamespace;

    /**
     * The directory to create the widget in, such as [app/Filament/Resources/Categories] for a widget with the FQN of [App\Filament\Resources\Categories\CategoryTree].
     */
    protected string $widgetsDirectory;

    /**
     * The fully qualified class name of the resource this widget is being created in, such as [App\Filament\Resources\Categories\CategoryResource].
     *
     * Null if this widget is not being created within a resource.
     */
    protected ?string $resourceFqn = null;

    public function handle(): int
    {
        try {
            // Page configuration
            $this->configureFqnEnd();
            $this->configurePanel(
                question: 'Which panel would you like to create this widget in?',
                initialQuestion: 'Would you like to create this widget in a panel?',
            );
            $this->configureHasResource();
            $this->configureCluster();
            $this->configureResource();
            $this->configureWidgetsLocation();

            // Location configuration
            $this->configureLocation();

            // Tree node configuration
            $this->askTreeNodeConfigurationForWidget();

            $this->createWidget();
        } catch (FailureCommandOutput) {
            return static::FAILURE;
        }

        $this->info("Tree widget [{$this->fqn}] created successfully.");

        return self::SUCCESS;
    }

    protected function configureFqnEnd(): void
    {
        $this->fqnEnd = (string) str($this->argument('name') ?? text(
            label: 'What is the widget name?',
            placeholder: 'CategoryTree',
            required: true,
        ))
            ->trim('/')
            ->trim('\\')
            ->trim(' ')
            ->studly()
            ->replace('/', '\\');
    }

    protected function configureHasResource(): void
    {
        if (! $this->panel) {
            $this->hasResource = false;

            return;
        }

        $this->hasResource = $this->option('resource') || confirm(
            label: 'Would you like to create this widget in a resource?',
            default: false,
        );
    }

    protected function configureCluster(): void
    {
        if (! $this->hasResource) {
            return;
        }

        $this->configureClusterFqn(
            initialQuestion: 'Is the resource in a cluster?',
            question: 'Which cluster is the resource in?',
        );

        if (blank($this->clusterFqn)) {
            return;
        }

        $this->configureClusterResourcesLocation();
    }

    protected function configureResource(): void
    {
        if (! $this->hasResource) {
            return;
        }

        $this->configureResourcesLocation(question: 'Which namespace would you like to search for resources in?');

        $this->resourceFqn = $this->askForResource(
            question: 'Which resource would you like to create this widget in?',
            initialResource: $this->option('resource'),
        );

        $pluralResourceBasenameBeforeResource = (string) str($this->resourceFqn)
            ->classBasename()
            ->beforeLast('Resource')
            ->plural();

        $resourceNamespacePartBeforeBasename = (string) str($this->resourceFqn)
            ->beforeLast('\\')
            ->classBasename();

        if ($pluralResourceBasenameBeforeResource === $resourceNamespacePartBeforeBasename) {
            $this->widgetsNamespace = (string) str($this->resourceFqn)
                ->beforeLast('\\')
                ->append('\\Widgets');
            $this->widgetsDirectory = (string) str((new ReflectionClass($this->resourceFqn))->getFileName())
                ->beforeLast(DIRECTORY_SEPARATOR)
                ->append('/Widgets');

            return;
        }

        $this->widgetsNamespace = "{$this->resourceFqn}\\Widgets";
        $this->widgetsDirectory = (string) str((new ReflectionClass($this->resourceFqn))->getFileName())
            ->beforeLast('.')
            ->append('/Widgets');
    }

    protected function configureWidgetsLocation(): void
    {
        if (filled($this->resourceFqn)) {
            return;
        }

        if (! $this->panel) {
            [
                $this->widgetsNamespace,
                $this->widgetsDirectory,
            ] = $this->askForLivewireComponentLocation(
                question: 'Where would you like to create the widget?',
            );

            return;
        }

        $directories = $this->panel->getWidgetDirectories();
        $namespaces = $this->panel->getWidgetNamespaces();

        foreach ($directories as $index => $directory) {
            if (str($directory)->startsWith(base_path('vendor'))) {
                unset($directories[$index]);
                unset($namespaces[$index]);
            }
        }

        if (count($namespaces) < 2) {
            $this->widgetsNamespace = (Arr::first($namespaces) ?? app()->getNamespace() . 'Filament\\Widgets');
            $this->widgetsDirectory = (Arr::first($directories) ?? app_path('Filament/Widgets/'));

            return;
        }

        $keyedNamespaces = array_combine(
            $namespaces,
            $namespaces,
        );

        $this->widgetsNamespace = search(
            label: 'Which namespace would you like to create this widget in?',
            options: function (?string $search) use ($keyedNamespaces): array {
                if (blank($search)) {
                    return $keyedNamespaces;
                }

                $search = str($search)->trim()->replace(['\\', '/'], '');

                return array_filter($keyedNamespaces, fn (string $namespace): bool => str($namespace)->replace(['\\', '/'], '')->contains($search, ignoreCase: true));
            },
        );
        $this->widgetsDirectory = $directories[array_search($this->widgetsNamespace, $namespaces)];
    }

    protected function configureLocation(): void
    {
        $this->fqn = $this->widgetsNamespace . '\\' . $this->fqnEnd;
    }

    protected function createWidget(): void
    {
        $path = (string) str("{$this->widgetsDirectory}\\{$this->fqnEnd}.php")
            ->replace('\\', '/')
            ->replace('//', '/');

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new FailureCommandOutput;
        }

        $this->writeFile($path, app(TreeWidgetClassGenerator::class, [
            'fqn' => $this->fqn,
            'modelFqn' => $this->treeModelFqn,
            'staticNodesPropertyName' => $this->treeStaticNodePropertyName,
        ]));
    }
}
