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

    public function getProofImageAttribute($value)
    {
        if (!$value) {
            return null;
        }

        if (str_starts_with($value, 'http://') || str_starts_with($value, 'https://')) {
            return $value;
        }

        $useR2 = config('filesystems.disks.r2_public.key') && config('filesystems.disks.r2_public.secret');
        $r2Url = rtrim(config('filesystems.disks.r2_public.url') ?? env('CLOUDFLARE_R2_PUBLIC_URL', ''), '/');

        if ($useR2) {
            $relativePath = ltrim(str_replace('/storage/', '', $value), '/');
            return $r2Url . '/' . $relativePath;
        }

        if (!str_starts_with($value, '/storage/')) {
            return '/storage/' . ltrim($value, '/');
        }

        return $value;
    }
}
