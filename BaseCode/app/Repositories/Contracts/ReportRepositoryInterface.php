<?php
namespace App\Repositories\Contracts;

interface ReportRepositoryInterface{
    public function create(array $data);
    public function findById(int $id);
    public function  update(int $id, array $data);
    public function getUserReports(int $userId);
}

?>