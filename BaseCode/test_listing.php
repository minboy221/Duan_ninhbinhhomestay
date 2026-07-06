<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

try {
    $s = app()->make(App\Services\RoomListingService::class);
    print_r($s->getLandlordPosts(21)->toArray());
} catch (\Exception $e) {
    echo $e->getMessage();
}
