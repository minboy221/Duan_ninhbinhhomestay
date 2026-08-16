<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Feature extends Model
{
    use HasFactory;

    protected $fillable = [
        'name',
        'feature_code',
        'description',
    ];

    public function plans()
    {
        return $this->belongsToMany(SubscriptionPlan::class, 'subscription_plan_feature', 'feature_id', 'plan_id')
            ->withPivot('feature_value');
    }

}
