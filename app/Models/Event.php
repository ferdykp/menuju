<?php

namespace App\Models;

use App\Models\Concerns\RecordsWorkspaceActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Event extends Model
{
    use SoftDeletes;
    use RecordsWorkspaceActivity;

    protected $fillable = ['workspace_id', 'created_by', 'title', 'event_type', 'event_date', 'start_time', 'end_time', 'timezone', 'venue_name', 'address', 'notes'];

    protected function casts(): array { return ['event_date' => 'date']; }

    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function agendaItems(): HasMany { return $this->hasMany(EventAgendaItem::class)->orderBy('sort_order')->orderBy('start_time'); }
    public function needs(): HasMany { return $this->hasMany(Need::class); }
    public function tasks(): HasMany { return $this->hasMany(Task::class); }
}
