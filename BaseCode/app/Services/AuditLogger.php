<?php
namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Support\Facades\Request;

class AuditLogger{
    public static function log(string $action, string $target, bool $sensitive = false):void{
        AuditLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'target'=> $target,
            'ip_address' => Request::ip(),
            'user_agent' => Request::userAgent(),
            'sensitive' => $sensitive,  
        ]);
    }
}

?>