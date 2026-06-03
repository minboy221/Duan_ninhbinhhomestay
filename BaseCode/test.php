<?php
require 'vendor/autoload.php';
$app = require_once 'bootstrap/app.php';
$app->make(Illuminate\Contracts\Console\Kernel::class)->bootstrap();

$lastVerification = \App\Models\UserVerification::latest('id')->first();
dump('Last user verification user_id: ' . $lastVerification->user_id);
dump('Last user verification kyc_status: ' . $lastVerification->kyc_status);

$lastBoardingHouse = \App\Models\BoardingHouse::latest('id')->first();
dump('Last boarding house user_id: ' . $lastBoardingHouse->user_id);
dump('Last boarding house status: ' . $lastBoardingHouse->status);
