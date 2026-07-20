<?php
require __DIR__.'/vendor/autoload.php';
$app = require_once __DIR__.'/bootstrap/app.php';
$kernel = $app->make(Illuminate\Contracts\Console\Kernel::class);
$kernel->bootstrap();

$landlordId = 21; // Assuming the logged-in user is 21
$boardingHouseId = 7;
$data = [
    'floor_id' => 6,
    'room_number' => 'Phòng Test',
    'price' => 1000,
    'area' => 20,
    'capacity' => 2,
    'status' => 'available',
];
$imageFiles = [];

$roomService = app(\App\Services\RoomService::class);
$room = $roomService->createRoom($landlordId, $data, $imageFiles, $boardingHouseId);

echo json_encode($room);
