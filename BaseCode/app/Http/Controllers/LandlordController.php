<?php

namespace App\Http\Controllers;

use Illuminate\Http\Request;
use Inertia\Inertia;

class LandlordController extends Controller
{
    public function dashboard()
    {
        return Inertia::render('Landlord/Dashboard');
    }

    public function profile()
    {
        return Inertia::render('Landlord/Profile');
    }

    public function rooms()
    {
        return Inertia::render('Landlord/Rooms/index');
    }

    public function listings()
    {
        return Inertia::render('Landlord/Listings/index');
    }

    public function listingCreate()
    {
        return Inertia::render('Landlord/Listings/Create');
    }

    public function appointments()
    {
        return Inertia::render('Landlord/Appointments/index');
    }

    public function tenants()
    {
        return Inertia::render('Landlord/Tenants/index');
    }

    public function contracts()
    {
        return Inertia::render('Landlord/Contracts/index');
    }

    public function invoices()
    {
        return Inertia::render('Landlord/Invoices/index');
    }

    public function finance()
    {
        return Inertia::render('Landlord/Finance/index');
    }
}
