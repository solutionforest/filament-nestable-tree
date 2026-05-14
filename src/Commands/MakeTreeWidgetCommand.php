<?php

namespace SolutionForest\FilamentNestableTree\Commands;

use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class MakeTreeWidgetCommand extends Command
{
    public $signature = 'make:filament-tree-widget
                        {name : The name of the widget class}
                        {--force : Overwrite the file if it already exists}';

    public $description = 'Create a new Filament tree widget';

    public function __construct(protected Filesystem $files)
    {
        parent::__construct();
    }

    public function handle(): int
    {
        $name = Str::studly($this->argument('name'));
        $path = app_path("Filament/Widgets/{$name}.php");

        if ($this->files->exists($path) && ! $this->option('force')) {
            $this->error("Widget [{$path}] already exists! Use --force to overwrite.");

            return self::FAILURE;
        }

        $this->files->ensureDirectoryExists(dirname($path));

        $stub = $this->files->get(__DIR__ . '/../../stubs/TreeWidget.php.stub');

        $namespace = 'App\\Filament\\Widgets';
        $stub = str_replace(
            ['{{ namespace }}', '{{ class }}'],
            [$namespace, $name],
            $stub,
        );

        $this->files->put($path, $stub);

        $this->info("Tree widget [{$path}] created successfully.");

        return self::SUCCESS;
    }
}
