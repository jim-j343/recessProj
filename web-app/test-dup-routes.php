<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

// Manually register the same route twice to see if it causes the exact exception!
Route::get('test-duplicate-uri', function() {})->name('test.dup');
Route::get('test-duplicate-uri', function() {})->name('test.dup');

try {
    app('router')->getRoutes()->compile();
    echo "Success!\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}

// Now test same name, different URI
Route::get('test-duplicate-uri-1', function() {})->name('test.dup.diff');
Route::get('test-duplicate-uri-2', function() {})->name('test.dup.diff');

try {
    app('router')->getRoutes()->compile();
    echo "Success!\n";
} catch (\Exception $e) {
    echo "Exception: " . $e->getMessage() . "\n";
}
