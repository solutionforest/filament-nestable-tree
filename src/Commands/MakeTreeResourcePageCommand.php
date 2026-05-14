<?php

namespace SolutionForest\FilamentNestableTree\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeTreeResourcePageCommand extends Command
{
    public $signature = 'make:filament-tree-resource-page
                        {name : The name of the page class}
                        {--resource= : The resource class name (e.g. CategoryResource)}
                        {--force : Overwrite the file if it already exists}';

    public $description = 'Create a new Filament tree resource list page';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $resource = $this->option('resource') ?? 'YourResource';
        $resource = Str::studly($resource);

        // Derive resource directory from resource name (strip "Resource" suffix for dir)
        $resourceDir = Str::beforeLast($resource, 'Resource');
        $path = app_path("Filament/Resources/{$resourceDir}/Pages/{$name}.php");

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->error("Resource page [{$path}] already exists! Use --force to overwrite.");

            return self::FAILURE;
        }

        $this->files->ensureDirectoryExists(dirname($path));

        $stub = $this->files->get(__DIR__ . '/../../stubs/TreeResourcePage.php.stub');

        $namespace = "App\\Filament\\Resources\\{$resourceDir}\\Pages";
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}', '{{ resource }}'],
            [$namespace, $name, "App\\Filament\\Resources\\{$resource}"],
            $stub,
        );

        $this->files->put($path, $stub);

        $this->info("Tree resource page [{$path}] created successfully.");

        return self::SUCCESS;
    }
}
