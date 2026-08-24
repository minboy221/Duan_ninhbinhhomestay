<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;

class Post extends Model
{
    use HasFactory;

    protected $fillable = [
        'title',
        'slug',
        'image',
        'summary',
        'content',
        'category',
        'tags',
        'author_id',
        'views',
    ];

    public function author()
    {
        return $this->belongsTo(User::class, 'author_id');
    }

    //phần nhận báo cáo
    public function reports(){
        return $this->morphMany(\App\Models\Report::class,'reportable');
    }
}
