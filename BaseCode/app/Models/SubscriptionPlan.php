<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class SubscriptionPlan extends Model
{
    use HasFactory;
    protected $fillable = [
        'name',
        'price',
        'duration_days',
        'badge',
        'sort_order',
        'description',
        'is_active',
    ];
    protected $casts = [
        'price' => 'decimal:2',
        'is_active' => 'boolean',
    ];

    public function features()
    {
        return $this->belongsToMany(Feature::class, 'subscription_plan_feature', 'plan_id', 'feature_id')
            ->withPivot('feature_value');
    }

    public function subscript()
    {
        return $this->hasMany(LandlordSubscription::class, 'plan_id');
    }
}
