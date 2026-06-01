<?php
namespace App\Repositories;
use App\Models\BoardingHouse;

class BoardingHouseRepository{
    public function createBoardingHouse(array $data)
    {
        return BoardingHouse::create($data);
    }
}

?>