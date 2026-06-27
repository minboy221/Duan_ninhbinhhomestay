<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$tablesToCheck = [
    'rooms',
    'room_service',
    'room_posts'
];

foreach ($tablesToCheck as $table) {
    if (Schema::hasTable($table)) {
        echo "Table \$table exists.\n";
    } else {
        echo "Table \$table DOES NOT exist.\n";
    }
}

$columnsToCheck = [
    ['rooms', 'current_people'],
    ['services', 'icon'],
    ['services', 'color'],
];

foreach ($columnsToCheck as $col) {
    if (Schema::hasColumn($col[0], $col[1])) {
        echo "Column {$col[0]}.{$col[1]} exists.\n";
    } else {
        echo "Column {$col[0]}.{$col[1]} DOES NOT exist.\n";
    }
}
