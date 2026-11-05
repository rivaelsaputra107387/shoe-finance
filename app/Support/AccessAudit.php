<?php

namespace App\Support;

use App\Models\Central\CentralAuditTrail;

class AccessAudit
{
    /**
     * Catat perubahan identitas dan akses di pusat, karena tidak terikat ke satu unit.
     */
    public static function record(string $action, string $subjectType, ?int $subjectId, ?array $old = null, ?array $new = null): void
    {
        CentralAuditTrail::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'subject_type' => $subjectType,
            'subject_id' => $subjectId,
            'old_data' => $old,
            'new_data' => $new,
            'ip_address' => request()?->ip(),
        ]);
    }
}
