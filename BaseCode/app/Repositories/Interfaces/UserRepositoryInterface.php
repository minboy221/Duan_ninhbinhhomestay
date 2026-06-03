<?php
namespace App\Repositories\Interfaces;

interface UserRepositoryInterface
{
    public function create(array $data);
    public function updateOrCreateGoogleUser($googleUser);
    public function updateUser($userId, array $data);
    public function findByEmail(string $email);
    public function findByGoogleId(string $googleId);
}