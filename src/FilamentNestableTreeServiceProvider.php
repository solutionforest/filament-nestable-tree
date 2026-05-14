<?php

namespace SolutionForest\FilamentNestableTree;

use Filament\Support\Assets\AlpineComponent;
use Filament\Support\Assets\Asset;
use Filament\Support\Assets\Css;
use Filament\Support\Facades\FilamentAsset;
use Filament\Support\Facades\FilamentIcon;
use Illuminate\Filesystem\Filesystem;
use Livewire\Features\SupportTesting\Testable;
use SolutionForest\FilamentNestableTree\Commands\MakeTreePageCommand;
use SolutionForest\FilamentNestableTree\Commands\MakeTreeResourcePageCommand;
use SolutionForest\FilamentNestableTree\Commands\MakeTreeWidgetCommand;
use SolutionForest\FilamentNestableTree\Testing\TestsFilamentNestableTree;
use Spatie\LaravelPackageTools\Package;
use Spatie\LaravelPackageTools\PackageServiceProvider;

class FilamentNestableTreeServiceProvider extends PackageServiceProvider
{
    public static string $name = 'filament-nestable-tree';

    public static string $viewNamespace = 'filament-nestable-tree';

    public function configurePackage(Package $package): void
    {
        /*
         * This class is a Package Service Provider
         *
         * More info: https://github.com/spatie/laravel-package-tools
         */
        $package->name(static::$name)
            ->hasCommands($this->getCommands());

        if (file_exists($package->basePath('/../resources/views'))) {
            $package->hasViews(static::$viewNamespace);
        }
    }

    public function packageRegistered(): void {}

    public function packageBooted(): void
    {
        FilamentAsset::register(
            $this->getAssets(),
            $this->getAssetPackageName()
        );

        FilamentAsset::registerScriptData(
            $this->getScriptData(),
            $this->getAssetPackageName()
        );

        // Icon Registration
        FilamentIcon::register($this->getIcons());

        // Handle Stubs
        if (app()->runningInConsole()) {
            foreach (app(Filesystem::class)->files(__DIR__ . '/../stubs/') as $file) {
                $this->publishes([
                    $file->getRealPath() => base_path("stubs/filament-nestable-tree/{$file->getFilename()}"),
                ], 'filament-nestable-tree-stubs');
            }
        }

        // Testing
        Testable::mixin(new TestsFilamentNestableTree);
    }

    protected function getAssetPackageName(): ?string
    {
        return 'solutionforest/filament-nestable-tree';
    }

    /**
     * @return array<Asset>
     */
    protected function getAssets(): array
    {
        return [
            AlpineComponent::make('tree-view', __DIR__ . '/../resources/dist/tree-view.js'),
            Css::make('tree-view', __DIR__ . '/../resources/dist/tree-view.css'),
        ];
    }

    /**
     * @return array<class-string>
     */
    protected function getCommands(): array
    {
        return [
            MakeTreePageCommand::class,
            MakeTreeWidgetCommand::class,
            MakeTreeResourcePageCommand::class,
        ];
    }

    /**
     * @return array<string>
     */
    protected function getIcons(): array
    {
        return [];
    }

    /**
     * @return array<string, mixed>
     */
    protected function getScriptData(): array
    {
        return [];
    }
}
