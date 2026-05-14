<?php

namespace SolutionForest\FilamentNestableTree\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeTreePageCommand extends Command
{
    public $signature = 'make:filament-tree-page
                        {name : The name of the page class}
                        {--force : Overwrite the file if it already exists}';

    public $description = 'Create a new Filament tree page';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $path = app_path("Filament/Pages/{$name}.php");

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->error("Page [{$path}] already exists! Use --force to overwrite.");

            return self::FAILURE;
        }

        $this->files->ensureDirectoryExists(dirname($path));

        $stub = $this->files->get(__DIR__ . '/../../stubs/TreePage.php.stub');

        $namespace = 'App\\Filament\\Pages';
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $name],
            $stub,
        );

        $this->files->put($path, $stub);

        $this->info("Tree page [{$path}] created successfully.");

        return self::SUCCESS;
    }
}
