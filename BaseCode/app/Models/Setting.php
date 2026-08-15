<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;

class Setting extends Model
{
    protected $table = 'settings';
    protected $primaryKey = 'key';
    
    // Khóa chính không tự tăng
    public $incrementing = false;
    protected $keyType = 'string';

    protected $fillable = ['key', 'value'];
}
