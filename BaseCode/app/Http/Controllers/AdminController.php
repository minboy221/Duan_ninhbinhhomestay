<?php

namespace App\Http\Controllers;

use App\Models\User;
use Illuminate\Http\Request;
use Inertia\Inertia;

class AdminController extends Controller
{
    /**
     * Display the admin dashboard.
     */
    public function index()
    {
        return Inertia::render('Admin/dashboard');
    }

    /**
     * Display a list of users.
     */
    public function users()
    {
        $users = User::all();
        return Inertia::render('Admin/users', [
            'users' => $users
        ]);
    }
}
