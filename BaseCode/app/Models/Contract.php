<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Contract extends Model
{
    use HasFactory;

    public static bool $allowImmutableUpdate = false;

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
        'ocr_status',
        'ocr_rejection_reason',
        'terms_accepted',
        'terms_accepted_at',
        'cancellation_reason',
        'cancelled_by',
        'liquidated_at',
        'deposit_refund_amount',
        'deposit_handling',
        'number_of_tenants',
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
        'terms_accepted' => 'boolean',
        'terms_accepted_at' => 'datetime',
        'liquidated_at' => 'datetime',
        'entry_readings_submitted_at' => 'datetime',
        'deposit_amount' => 'decimal:2',
        'monthly_rent' => 'decimal:2',
        'deposit_refund_amount' => 'decimal:2',
        'number_of_tenants' => 'integer',
    ];

    /**
     * Boot function from Laravel.
     */
    protected static function booted()
    {
        parent::booted();

        static::updating(function ($contract) {
            // Allow updates if explicitly permitted (e.g. extension or status change)
            if (self::$allowImmutableUpdate) {
                return;
            }

            // Prevent changing immutable fields if contract remains active without extension flag
            if ($contract->getOriginal('status') === 'active' && $contract->status === 'active') {
                $immutableFields = ['start_date', 'deposit_amount'];
                foreach ($immutableFields as $field) {
                    if ($contract->isDirty($field)) {
                        throw new \Exception("Cannot update {$field} because the contract is active.");
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

    /**
     * Lịch sử gia hạn hợp đồng
     */
    public function extensions(): HasMany
    {
        return $this->hasMany(ContractExtension::class, 'contract_id')->orderBy('created_at', 'desc');
    }

    /**
     * Người thực hiện hủy
     */
    public function canceller(): BelongsTo
    {
        return $this->belongsTo(User::class, 'cancelled_by');
    }

    /**
     * Phần nhận báo cáo
     */
    public function reports()
    {
        return $this->morphMany(\App\Models\Report::class, 'reportable');
    }
}
