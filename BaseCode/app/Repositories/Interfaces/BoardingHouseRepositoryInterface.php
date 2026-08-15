<?php
namespace App\Repositories\Interfaces;

interface BoardingHouseRepositoryInterface
{
    public function createBoardingHouse(array $data);
    public function findById(int $id);
    public function getPendingBoardingHouses();
    public function getLandlordBoardingHouses(int $userId);
    public function getLandlordBoardingHousesHistory(int $userId);
    public function updateStatus(int $id, string $status);
}
