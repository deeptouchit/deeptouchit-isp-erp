<?php
namespace App\Traits;

use App\Models\ActivityLog;

trait AuditLoggable
{
    protected function logActivity(string $action, array $data = [], $oldData = null): void
    {
        ActivityLog::create([
            'user_id' => auth()->id(),
            'action' => $action,
            'description' => $data['description'] ?? $action,
            'ip_address' => request()->ip(),
            'user_agent' => request()->userAgent(),
            'old_values' => $oldData,
            'new_values' => $data
        ]);
    }
}
