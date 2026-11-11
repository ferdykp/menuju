<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Milestone extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = ['workspace_id', 'created_by', 'title', 'description', 'category', 'target_date', 'status', 'sort_order'];

    protected function casts(): array
    {
        return ['target_date' => 'date'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('due_at')->orderBy('id');
    }
}
