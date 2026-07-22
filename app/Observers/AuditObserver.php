<?php

namespace App\Observers;

use App\Models\AuditTrail;
use Illuminate\Database\Eloquent\Model;

class AuditObserver
{
    /**
     * Handle the "created" event.
     */
    public function created(Model $model): void
    {
        $this->logAudit($model, 'create', null, $model->toArray());
    }

    /**
     * Handle the "updated" event.
     */
    public function updated(Model $model): void
    {
        $oldData = collect($model->getOriginal())
            ->only(array_keys($model->getChanges()))
            ->toArray();

        $newData = $model->getChanges();

        // Remove timestamps from audit data to reduce noise
        unset($oldData['updated_at'], $newData['updated_at']);

        if (empty($newData)) {
            return;
        }

        // Detect special actions
        $action = 'update';
        if ($model->getTable() === 'fiscal_periods' && isset($newData['status']) && $newData['status'] === 'closed') {
            $action = 'close_period';
        }

        $this->logAudit($model, $action, $oldData, $newData);
    }

    /**
     * Handle the "deleted" event (including soft deletes).
     */
    public function deleted(Model $model): void
    {
        $this->logAudit($model, 'delete', $model->toArray(), null);
    }

    /**
     * Write the audit trail record.
     */
    private function logAudit(Model $model, string $action, ?array $oldData, ?array $newData): void
    {
        // Avoid logging audit trail records themselves (prevent infinite loop)
        if ($model instanceof AuditTrail) {
            return;
        }

        AuditTrail::create([
            'user_id' => auth()->id(),
            'table_name' => $model->getTable(),
            'record_id' => $model->id,
            'action' => $action,
            'old_data' => $oldData,
            'new_data' => $newData,
        ]);
    }
}
