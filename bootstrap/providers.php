<?php

// Dev-only packages (dont-discover): register only when present so Docker --no-dev vendor matches bootstrap/cache.
$providers = [];

foreach (
    [
        \NunoMaduro\Collision\Adapters\Laravel\CollisionServiceProvider::class,
        \Laravel\Pail\PailServiceProvider::class,
        \Laravel\Sail\SailServiceProvider::class,
    ] as $class
) {
    if (class_exists($class)) {
        $providers[] = $class;
    }
}

$providers[] = App\Providers\AppServiceProvider::class;

return $providers;
