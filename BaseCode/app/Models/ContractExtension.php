<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class ContractExtension extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'old_end_date',
        'new_end_date',
        'old_monthly_rent',
        'new_monthly_rent',
        'tenant_cccd_number',
        'verified_document_paths',
        'notes',
        'created_by',
    ];

    protected $casts = [
        'old_end_date' => 'date',
        'new_end_date' => 'date',
        'old_monthly_rent' => 'decimal:2',
        'new_monthly_rent' => 'decimal:2',
        'verified_document_paths' => 'array',
    ];

    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }
}
