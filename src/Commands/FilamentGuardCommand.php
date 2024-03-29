<?php

namespace Iotronlab\FilamentMultiGuard\Commands;

use Filament\Support\Commands\Concerns\CanManipulateFiles;
use Filament\Support\Commands\Concerns\CanValidateInput;
use Illuminate\Console\Command;
use Illuminate\Filesystem\Filesystem;
use Illuminate\Support\Str;

class FilamentGuardCommand extends Command
{
    use CanManipulateFiles;
    use CanValidateInput;

    protected $signature = 'make:filament-guard {name?} {--f|force}';

    protected $description = 'Create a Filament middleware for context';

    public function handle(): int
    {
        $context = Str::of($this->getContextInput())
            ->trim('/')
            ->trim('\\')
            ->trim(' ')
            ->replace('/', '\\');

        $this->copyStubs($context);

        return self::SUCCESS;
    }

    protected function copyStubs($context)
    {
        $directoryPath = app_path(
            (string) $context
                ->replace('\\', '/')
        );
        $contextName = $context->afterLast('\\')->kebab();

        $middlewareNamespace = $context
            ->replace('\\', '\\\\')
            ->prepend('\\')
            ->prepend('App')
            ->append('\\')
            ->append('Middleware');

        $middlewareClass = $context->afterLast('\\')->append('Middleware');

        $middlewarePath = $middlewareClass
            ->prepend($directoryPath.'/Middleware/')
            ->append('.php');

        if (! $this->option('force') && $this->checkForCollision([$middlewarePath])) {
            return static::INVALID;
        }

        $this->copyStubToApp('ContextMiddleware', $middlewarePath, [
            'class' => (string) $middlewareClass,
            'name' => (string) $contextName,
            'namespace' => (string) $middlewareNamespace,
        ]);

        $loginClass = $context->afterLast('\\')->append('Login');

        $loginPath = $loginClass
            ->prepend(app_path('Http/Livewire/'))
            ->append('.php');

        if (! $this->option('force') && $this->checkForCollision([$loginPath])) {
            return static::INVALID;
        }

        $this->copyStubToApp('ContextLogin', $loginPath, [
            'class' => (string) $loginClass,
            'name' => (string) $contextName,
        ]);

        $dashboardNamespace = $context
            ->replace('\\', '\\\\')
            ->prepend('\\')
            ->prepend('App')
            ->append('\\')
            ->append('Pages');

        if (! $this->option('force') && $this->checkForCollision([$directoryPath.'/Pages/Dashboard.php'])) {
            return static::INVALID;
        }

        $this->copyStubToApp('Dashboard', $directoryPath.'/Pages/Dashboard.php', [
            'namespace' => (string) $dashboardNamespace,
        ]);
    }

    protected function getContextInput(): string
    {
        return $this->validateInput(
            fn () => $this->argument('name') ?? $this->askRequired('Name (e.g. `FilamentTeams`)', 'name'),
            'name',
            ['required', 'not_in:filament']
        );
    }

    protected function copyStubToApp(string $stub, string $targetPath, array $replacements = []): void
    {
        $filesystem = app(Filesystem::class);

        if (! $this->fileExists($stubPath = base_path("stubs/filament/{$stub}.stub"))) {
            $stubPath = __DIR__."/../../stubs/{$stub}.stub";
        }

        $stub = Str::of($filesystem->get($stubPath));

        foreach ($replacements as $key => $replacement) {
            $stub = $stub->replace("{{ {$key} }}", $replacement);
        }

        $stub = (string) $stub;

        $this->writeFile($targetPath, $stub);
    }
}
