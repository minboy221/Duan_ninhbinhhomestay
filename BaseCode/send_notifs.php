<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make('Illuminate\Contracts\Console\Kernel')->bootstrap();

$admins = App\Models\User::where('role', 'admin')->get();
foreach(App\Models\RoomPost::where('status', 'pending')->get() as $post) {
    Illuminate\Support\Facades\Notification::send($admins, new App\Notifications\NewRoomPostNotification($post));
    echo "Sent for post " . $post->id . "\n";
}
