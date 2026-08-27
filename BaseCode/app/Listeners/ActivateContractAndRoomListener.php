<?php

namespace App\Listeners;

use App\Events\ContractSignedEvent;
use Illuminate\Contracts\Queue\ShouldQueue;
use Illuminate\Queue\InteractsWithQueue;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class ActivateContractAndRoomListener
{
    /**
     * Create the event listener.
     */
    public function __construct()
    {
        //
    }

    /**
     * Handle the event.
     */
    public function handle(ContractSignedEvent $event): void
    {
        $contract = $event->contract;

        try {
            DB::transaction(function () use ($contract) {
                // 1. Change contract status to active and set signed_at
                $contract->status = 'active';
                $contract->signed_at = now();
                $contract->save();

                // 2. Change room status to rented
                $room = $contract->room;
                if ($room) {
                    $room->status = 'rented';
                    $room->save();
                }
                event(new \App\Events\RoomStatusUpdated($room->id,'rented'));
            });
        } catch (\Exception $e) {
            Log::error("Failed to activate contract and room: " . $e->getMessage());
            throw $e;
        }
    }
}
