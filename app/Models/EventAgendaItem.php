<?php

namespace App\Models;

use App\Models\Concerns\RecordsWorkspaceActivity;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAgendaItem extends Model
{
    use RecordsWorkspaceActivity;
    protected $fillable = ['event_id', 'title', 'start_time', 'end_time', 'location', 'notes', 'sort_order'];
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
}
