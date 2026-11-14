<?php

namespace App\Models\Concerns;

use App\Models\WorkspaceActivity;
use Illuminate\Support\Facades\Auth;

trait RecordsWorkspaceActivity
{
    protected static function bootRecordsWorkspaceActivity(): void
    {
        static::created(fn ($model) => $model->recordWorkspaceActivity('created'));
        static::updated(fn ($model) => $model->recordWorkspaceActivity('updated'));
        static::deleted(fn ($model) => $model->recordWorkspaceActivity('deleted'));
    }

    protected function recordWorkspaceActivity(string $action): void
    {
        $workspaceId = $this->getAttribute('workspace_id');
        if (! $workspaceId) {
            foreach (['event', 'savingsGoal', 'goal', 'paymentObligation'] as $relation) {
                if (method_exists($this, $relation) && ($related = $this->{$relation})) {
                    $workspaceId = $related->workspace_id ?? $related->workspace?->id;
                    if ($workspaceId) break;
                }
            }
        }
        if (! $workspaceId && method_exists($this, 'workspace')) $workspaceId = $this->workspace?->id;
        if (! $workspaceId) return;

        $label = $this->getAttribute('title') ?: $this->getAttribute('name') ?: $this->getAttribute('description') ?: class_basename($this);
        $verb = ['created' => 'menambahkan', 'updated' => 'memperbarui', 'deleted' => 'menghapus'][$action] ?? $action;
        WorkspaceActivity::create([
            'workspace_id' => $workspaceId,
            'actor_id' => Auth::id() ?: ($action === 'created' ? $this->getAttribute('created_by') : null),
            'action' => $action,
            'subject_type' => class_basename($this),
            'subject_id' => $this->getKey(),
            'summary' => mb_substr("{$verb} {$label}", 0, 220),
            'occurred_at' => now(),
        ]);
    }
}
