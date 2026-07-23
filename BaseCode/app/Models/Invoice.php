<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Invoice extends Model
{
    use HasFactory;

    protected $fillable = [
        'contract_id',
        'invoice_code',
        'billing_month',
        'total_amount',
        'status',
        'due_date',
        'paid_at',
    ];

    protected $casts = [
        'total_amount' => 'decimal:2',
        'paid_at' => 'datetime',
        'due_date' => 'date',
    ];

    /**
     * Hợp đồng
     */
    public function contract(): BelongsTo
    {
        return $this->belongsTo(Contract::class, 'contract_id');
    }

    /**
     * Danh sách các khoản phí chi tiết của hóa đơn
     */
    public function details(): HasMany
    {
        return $this->hasMany(InvoiceDetail::class, 'invoice_id');
    }

     //phần nhận báo cáo
    public function reports(){
        return $this->morphMany(\App\Models\Report::class,'reportable');
    }
}
