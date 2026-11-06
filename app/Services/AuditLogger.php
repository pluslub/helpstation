<?php

namespace App\Services;

use Illuminate\Support\Facades\Log;

class AuditLogger
{
    public function log(string $action,
                        ?int $staffId = null,
                        ?string $table = null,
                        ?int $targetId = null,
                        array $detail = [],
                        ?string $ip = null) {

        $logArray = [   'staff_id' => $staffId,
                        'table' => $table,
                        'target_id' => $targetId,
                        'detail' =>$detail,
                        'ip' =>$ip ];

        Log::channel('audit')->info($action, $logArray);
    }
}
