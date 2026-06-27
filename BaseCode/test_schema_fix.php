<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

try {
    Schema::table('rooms', function (Blueprint $table) {
        $table->dropForeign('rooms_property_id_foreign');
    });
    echo "Dropped foreign key successfully.\n";
} catch (\Exception $e) {
    echo "Could not drop foreign key: " . $e->getMessage() . "\n";
}

try {
    Schema::table('rooms', function (Blueprint $table) {
        $table->renameColumn('property_id', 'boarding_house_id');
    });
    echo "Renamed column successfully.\n";
} catch (\Exception $e) {
    echo "Could not rename column: " . $e->getMessage() . "\n";
}

try {
    Schema::table('rooms', function (Blueprint $table) {
        $table->foreign('boarding_house_id')->references('id')->on('boarding_houses')->onDelete('cascade');
    });
    echo "Added new foreign key successfully.\n";
} catch (\Exception $e) {
    echo "Could not add new foreign key: " . $e->getMessage() . "\n";
}

