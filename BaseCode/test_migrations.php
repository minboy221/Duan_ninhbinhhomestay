<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();
$dbMigs = DB::table('migrations')->pluck('migration')->toArray();
$files = array_map(function($f){ return basename($f, '.php'); }, glob(database_path('migrations/*.php')));
$diff = array_diff($files, $dbMigs);
echo json_encode(array_values($diff));
