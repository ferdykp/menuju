<?php

namespace App\Models;

use App\Models\Concerns\RecordsWorkspaceActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class WorkspaceDecision extends Model
{
    use SoftDeletes;
    use RecordsWorkspaceActivity;
    protected $fillable = ['workspace_id', 'created_by', 'event_id', 'title', 'context', 'options', 'status', 'outcome'];
    protected function casts(): array { return ['options' => 'array']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
