<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class LandlordSubscription extends Model
{
    use HasFactory;

    protected $fillable = [
        'user_id',
        'plan_id',
        'payment_code',
        'price_at_purchase',
        'start_date',
        'end_date',
        'proof_image',
        'payment_method',
        'status',
        'admin_note',
        'approved_by',
        'approved_at',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'approved_at' => 'datetime',
        'price_at_purchase' => 'decimal: 2',
    ];

    public function user(){
        return $this->belongsTo(User::class);
    }
    public function plan(){
        return $this->belongsTo(SubscriptionPlan::class,'plan_id');
    }
    public function approver(){
        return $this->belongsTo(User::class, 'approved_by');
    }
}
