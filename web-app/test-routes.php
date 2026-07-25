<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = app('router')->getRoutes();
$name = 'groups.show';

foreach ($routes->getRoutes() as $route) {
    if ($route->getName() === $name) {
        echo "Route with name '{$name}' has URI: " . $route->uri() . "\n";
    }
}
