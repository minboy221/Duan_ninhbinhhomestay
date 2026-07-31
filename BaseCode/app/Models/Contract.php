<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    protected $fillable = [
        'tenant_id',
        'room_id',
        'start_date',
        'end_date',
        'deposit_amount',
        'contract_file_path',
        'signed_contract_image',
        'monthly_rent',
        'status',
        'signed_at',
        'entry_elec_index',
        'entry_elec_image',
        'entry_water_index',
        'entry_water_image',
        'entry_readings_submitted_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'signed_at' => 'datetime',
        'entry_readings_submitted_at' => 'datetime',
        'deposit_amount' => 'decimal:2',
        'monthly_rent' => 'decimal:2',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function booted()
    {
        parent::booted();

        static::updating(function ($contract) {
            // Prevent changing immutable fields if the contract is active
            if ($contract->getOriginal('status') === 'active') {
                $immutableFields = ['start_date', 'end_date', 'monthly_rent'];
                foreach ($immutableFields as $field) {
                    if ($contract->isDirty($field)) {
                        throw new \Exception("Cannot update {$field} because the contract is active (immutable).");
                    }
                }
            }
        });
    }

    /**
     * Khách thuê
     */
    public function tenant(): BelongsTo
    {
        return $this->belongsTo(User::class, 'tenant_id');
    }

    /**
     * Phòng trọ
     */
    public function room(): BelongsTo
    {
        return $this->belongsTo(Room::class, 'room_id');
    }

    /**
     * Hóa đơn hàng tháng của hợp đồng này
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class, 'contract_id');
    }

     //phần nhận báo cáo
    public function reports(){
        return $this->morphMany(\App\Models\Report::class,'reportable');
    }
}
