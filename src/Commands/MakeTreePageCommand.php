<?php

namespace SolutionForest\FilamentNestableTree\Commands;

use Filament\Support\Commands\Concerns\CanAskForResource;
use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Filament\Support\Commands\Concerns\HasCluster;
use Filament\Support\Commands\Concerns\HasClusterPagesLocation;
use Filament\Support\Commands\Concerns\HasPanel;
use Filament\Support\Commands\Concerns\HasResourcesLocation;
use Filament\Support\Commands\Exceptions\FailureCommandOutput;
use Illuminate\Console\Command;
use Illuminate\Support\Arr;
use ReflectionClass;
use SolutionForest\FilamentNestableTree\Commands\Concerns\CanAskTreeNodeConfiguration;
use SolutionForest\FilamentNestableTree\Commands\FileGenerators\TreePageClassGenerator;

use function Laravel\Prompts\confirm;
use function Laravel\Prompts\search;
use function Laravel\Prompts\text;

class MakeTreePageCommand extends Command
{
    use CanAskForResource;
    use CanAskTreeNodeConfiguration;
    use CanManipulateFiles;
    use HasCluster;
    use HasClusterPagesLocation;
    use HasPanel;
    use HasResourcesLocation;

    public $signature = 'make:filament-tree-page
                        {name? : The name of the page class}
                        {--C|cluster= : The cluster to create the page in (optional)}
                        {--panel= : The panel to create the page in (optional)}
                        {--R|resource= : The resource to create the page in (optional)}
                        {--resource-namespace= : The namespace of the resource class (optional), such as [App\\Filament\\Resources]}
                        {--force : Overwrite the file if it already exists}';

    public $description = 'Create a new Filament tree page';

    protected $aliases = ['filament:make-tree-page', 'filament:tree-page'];

    /**
     * The fully qualified class name of the page to create, such as [App\Filament\Pages\Settings\ManageSettings].
     *
     * @var class-string
     */
    protected string $fqn;

    /**
     * The class name ending of the page, such as [ManageSettings] for a page with the FQN of [App\Filament\Pages\Settings\ManageSettings].
     */
    protected string $fqnEnd;

    // ── Configuration properties ────────────────────────────────────────────────

    /**
     * The namespace to create the page in, such as [App\Filament\Pages\Settings] for a page with the FQN of [App\Filament\Pages\Settings\ManageSettings].
     */
    protected string $pagesNamespace;

    /**
     * The directory to create the page in, such as [app/Filament/Pages/Settings] for a page with the FQN of [App\Filament\Pages\Settings\ManageSettings].
     */
    protected string $pagesDirectory;

    /**
     * Whether or not this page is being created within a filament resource.
     */
    protected bool $hasResource = false;

    /**
     * The fully qualified class name of the resource this page is being created in, such as [App\Filament\Resources\SettingsResource].
     *
     * Null if this page is not being created within a resource.
     */
    protected ?string $resourceFqn = null;

    public function handle(): int
    {
        try {
            // Page configuration
            $this->configureFqnEnd();
            $this->configurePanel(question: 'Which panel would you like to create this page in?');
            $this->configureHasResource();
            $this->configureCluster();
            $this->configureResource();
            $this->configurePagesLocation();

            // Location configuration
            $this->configureLocation();

            // Tree node configuration
            $this->askTreeNodeConfigurationForPage($this->hasResource);

            $this->createPage();
        } catch (FailureCommandOutput) {
            return static::FAILURE;
        }

        $this->components->info("Tree page [{$this->fqn}] created successfully.");

        if (filled($this->resourceFqn)) {
            $this->components->info("Make sure to register the page in [{$this->resourceFqn}::getPages()].");
        } elseif (empty($this->panel->getPageNamespaces())) {
            $this->components->info('Make sure to register the page with [pages()] or discover it with [discoverPages()] in the panel service provider.');
        }

        return self::SUCCESS;
    }

    protected function configureFqnEnd(): void
    {
        $this->fqnEnd = (string) str($this->argument('name') ?? text(
            label: 'What is the page name?',
            placeholder: 'ManageSettings',
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
        $this->hasResource = $this->option('resource') || confirm(
            label: 'Would you like to create this page in a resource?',
            default: false,
        );
    }

    protected function configureCluster(): void
    {
        if ($this->hasResource) {
            $this->configureClusterFqn(
                initialQuestion: 'Is the resource in a cluster?',
                question: 'Which cluster is the resource in?',
            );
        } else {
            $this->configureClusterFqn(
                initialQuestion: 'Would you like to create this page in a cluster?',
                question: 'Which cluster would you like to create this page in?',
            );
        }

        if (blank($this->clusterFqn)) {
            return;
        }

        $this->configureClusterPagesLocation();
        $this->configureClusterResourcesLocation();
    }

    protected function configureResource(): void
    {
        if (! $this->hasResource) {
            return;
        }

        $this->configureResourcesLocation(question: 'Which namespace would you like to search for resources in?');

        $this->resourceFqn = $this->askForResource(
            question: 'Which resource would you like to create this page in?',
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
            $this->pagesNamespace = (string) str($this->resourceFqn)
                ->beforeLast('\\')
                ->append('\\Pages');
            $this->pagesDirectory = (string) str((new ReflectionClass($this->resourceFqn))->getFileName())
                ->beforeLast(DIRECTORY_SEPARATOR)
                ->append('/Pages');

            return;
        }

        $this->pagesNamespace = "{$this->resourceFqn}\\Pages";
        $this->pagesDirectory = (string) str((new ReflectionClass($this->resourceFqn))->getFileName())
            ->beforeLast('.')
            ->append('/Pages');
    }

    protected function configurePagesLocation(): void
    {
        if (filled($this->resourceFqn)) {
            return;
        }

        if (filled($this->clusterFqn)) {
            return;
        }

        $directories = $this->panel->getPageDirectories();
        $namespaces = $this->panel->getPageNamespaces();

        foreach ($directories as $index => $directory) {
            if (str($directory)->startsWith(base_path('vendor'))) {
                unset($directories[$index]);
                unset($namespaces[$index]);
            }
        }

        if (count($namespaces) < 2) {
            $this->pagesNamespace = (Arr::first($namespaces) ?? app()->getNamespace() . 'Filament\\Pages');
            $this->pagesDirectory = (Arr::first($directories) ?? app_path('Filament/Pages/'));

            return;
        }

        $keyedNamespaces = array_combine(
            $namespaces,
            $namespaces,
        );

        $this->pagesNamespace = search(
            label: 'Which namespace would you like to create this page in?',
            options: function (?string $search) use ($keyedNamespaces): array {
                if (blank($search)) {
                    return $keyedNamespaces;
                }

                $search = str($search)->trim()->replace(['\\', '/'], '');

                return array_filter($keyedNamespaces, fn (string $namespace): bool => str($namespace)->replace(['\\', '/'], '')->contains($search, ignoreCase: true));
            },
        );
        $this->pagesDirectory = $directories[array_search($this->pagesNamespace, $namespaces)];
    }

    protected function configureLocation(): void
    {
        $this->fqn = $this->pagesNamespace . '\\' . $this->fqnEnd;
    }

    protected function createPage(): void
    {
        $path = (string) str("{$this->pagesDirectory}\\{$this->fqnEnd}.php")
            ->replace('\\', '/')
            ->replace('//', '/');

        if (! $this->option('force') && $this->checkForCollision($path)) {
            throw new FailureCommandOutput;
        }

        $this->writeFile($path, app(TreePageClassGenerator::class, [
            'fqn' => $this->fqn,
            'resourceFqn' => $this->resourceFqn,
            'clusterFqn' => $this->clusterFqn,
            'modelFqn' => $this->treeModelFqn,
            'staticNodesPropertyName' => $this->treeStaticNodePropertyName,
            'multipleTreeKeys' => $this->multipleTreeKeys,
        ]));
    }
}
