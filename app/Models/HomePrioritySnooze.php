<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class HomePrioritySnooze extends Model
{
    protected $fillable = ['workspace_id', 'user_id', 'priority_key', 'snoozed_until'];
    protected function casts(): array { return ['snoozed_until' => 'date']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
}
