<?php

require __DIR__ . '/vendor/autoload.php';
$app = require_once __DIR__ . '/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

use Illuminate\Support\Facades\DB;
use App\Models\User;

$user = User::where('name', 'Demo User')
    ->orWhere('email', 'user@gmail.com')
    ->orWhere('phone', '0987654321')
    ->first();

if (!$user) {
    echo "User not found.\n";
    exit;
}

$id = $user->id;
echo "Found User ID: {$id} ({$user->name} - {$user->email})\n";

DB::transaction(function () use ($id, $user) {
    // Check tables and delete records
    $tables = DB::select('SHOW TABLES');
    $dbName = DB::getDatabaseName();
    $tableKey = "Tables_in_" . $dbName;

    foreach ($tables as $t) {
        $table = $t->$tableKey ?? array_values((array)$t)[0];
        if ($table === 'users') continue;

        $cols = DB::getSchemaBuilder()->getColumnListing($table);
        $deleted = 0;

        if (in_array('user_id', $cols)) {
            $deleted += DB::table($table)->where('user_id', $id)->delete();
        }
        if (in_array('tenant_id', $cols) && $table !== 'users') {
            $deleted += DB::table($table)->where('tenant_id', $id)->delete();
        }
        if (in_array('landlord_id', $cols) && $table !== 'users') {
            $deleted += DB::table($table)->where('landlord_id', $id)->delete();
        }

        if ($deleted > 0) {
            echo "Deleted {$deleted} records from table '{$table}'.\n";
        }
    }

    $user->delete();
    echo "Successfully deleted user ID {$id} ({$user->name}).\n";
});
