<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class WorkspaceActivity extends Model
{
    public $timestamps = false;
    protected $fillable = ['workspace_id', 'actor_id', 'action', 'subject_type', 'subject_id', 'summary', 'occurred_at'];
    protected function casts(): array { return ['occurred_at' => 'datetime']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function actor(): BelongsTo { return $this->belongsTo(User::class, 'actor_id'); }
}
