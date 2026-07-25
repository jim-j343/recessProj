<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$routes = app('router')->getRoutes();
$name = 'groups.show';

foreach ($routes->getRoutes() as $route) {
    if ($route->getName() === $name) {
        echo "Route Name: {$route->getName()}\n";
        echo "URI: {$route->uri()}\n";
        echo "Action Name: {$route->getActionName()}\n";
        echo "Methods: " . implode('|', $route->methods()) . "\n";
        echo "----------------------\n";
    }
}
