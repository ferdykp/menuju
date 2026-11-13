<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class EventAgendaItem extends Model
{
    protected $fillable = ['event_id', 'title', 'start_time', 'end_time', 'location', 'notes', 'sort_order'];
    public function event(): BelongsTo { return $this->belongsTo(Event::class); }
}
