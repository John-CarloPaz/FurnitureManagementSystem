<?php

namespace App\Domain\ModelGeneration;

use App\Domain\ModelGeneration\Contracts\ModelGenerator;
use App\Domain\ModelGeneration\Providers\MeshyModelGenerator;
use Illuminate\Support\ServiceProvider;
use InvalidArgumentException;

class ModelGenerationServiceProvider extends ServiceProvider
{
    /** @var array<string, class-string<ModelGenerator>> */
    private const DRIVERS = [
        'meshy' => MeshyModelGenerator::class,
    ];

    public function register(): void
    {
        $this->app->singleton(ModelGenerator::class, function () {
            $provider = (string) config('model_generation.provider', 'meshy');
            $driver = self::DRIVERS[$provider] ?? throw new InvalidArgumentException("Unknown model generation provider [{$provider}].");

            return $this->app->make($driver);
        });
    }
}
