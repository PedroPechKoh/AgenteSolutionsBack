<?php
require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Facades\DB;

$table = 'property_components';
if (Schema::hasTable($table)) {
    echo "Table '$table' exists.\nColumns:\n";
    $columns = Schema::getColumnListing($table);
    foreach ($columns as $column) {
        echo "- $column\n";
    }
} else {
    echo "Table '$table' DOES NOT exist.\n";
}

$table2 = 'property_areas';
if (Schema::hasTable($table2)) {
    echo "\nTable '$table2' exists.\nColumns:\n";
    $columns = Schema::getColumnListing($table2);
    foreach ($columns as $column) {
        echo "- $column\n";
    }
}
