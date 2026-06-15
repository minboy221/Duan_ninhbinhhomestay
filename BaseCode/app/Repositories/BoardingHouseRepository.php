<?php
namespace App\Repositories;
use App\Models\BoardingHouse;

class BoardingHouseRepository
{
    public function createBoardingHouse(array $data)
    {
        return BoardingHouse::updateOrCreate(
            ['user_id' => $data['user_id']],
            $data                           
        );
    }
}

?>