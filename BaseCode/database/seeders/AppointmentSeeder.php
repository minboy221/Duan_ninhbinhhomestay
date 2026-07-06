<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;
use Carbon\Carbon;

class AppointmentSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $tenant = DB::table('users')->where('email', 'nguoithue@staywork.com')->first();
        $landlord = DB::table('users')->where('email', 'chutro@staywork.com')->first();
        $room = DB::table('rooms')->first();

        if ($tenant && $landlord && $room) {
            DB::table('appointments')->insert([
                [
                    'user_id' => $tenant->id,
                    'landlord_id' => $landlord->id,
                    'room_id' => $room->id,
                    'date' => Carbon::today()->format('Y-m-d'),
                    'time' => '10:00:00',
                    'note' => 'Hẹn xem phòng vào buổi sáng hôm nay.',
                    'status' => 'approved',
                    'notified' => false,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
                [
                    'user_id' => $tenant->id,
                    'landlord_id' => $landlord->id,
                    'room_id' => $room->id,
                    'date' => Carbon::tomorrow()->format('Y-m-d'),
                    'time' => '14:30:00',
                    'note' => 'Hẹn xem phòng vào chiều mai.',
                    'status' => 'pending',
                    'notified' => false,
                    'created_at' => Carbon::now(),
                    'updated_at' => Carbon::now(),
                ],
            ]);
        }
    }
}
