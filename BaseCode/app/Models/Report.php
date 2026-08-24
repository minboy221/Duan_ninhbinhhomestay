<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use App\Traits\Hashidable;
class Report extends Model
{
    use HasFactory, Hashidable;

    protected $fillable = [
        'reporter_id',
        'reportable_type',
        'reportable_id',
        'reason',
        'description',
        'evidence_images',
        'status',
        'admin_note',
        'resolved_by',
        'resolved_at',
        'negotiation_deadline',
        'target_resolved',
        'reporter_resolved',
        'response_note',
        'response_evidence',
    ];
    protected $casts = [
        'evidence_images' => 'array',
        'response_evidence' => 'array',
        'resolved_at' => 'datetime',
        'negotiation_deadline' => 'datetime',
        'target_resolved' => 'boolean',
        'reporter_resolved' => 'boolean',
    ];

    protected $appends = ['hash_id'];

    //lấy đối tượng bị báo cáo (bài đăng,cơ sở,hợp đồng,hoá đơn)
    public function reportable()
    {
        return $this->morphTo();
    }
    //người tạo báo cáo
    public function reporter()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }

    public function user()
    {
        return $this->belongsTo(User::class, 'reporter_id');
    }
    //admin xử lý
    public function resolver()
    {
        return $this->belongsTo(User::class, 'resolved_by');
    }
}
