<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class Guest extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'workspace_id', 'created_by', 'name', 'email', 'phone', 'group_name', 'side',
        'invitation_status', 'rsvp_status', 'plus_ones', 'notes',
    ];

    protected function casts(): array
    {
        return ['plus_ones' => 'integer'];
    }

    public function workspace(): BelongsTo
    {
        return $this->belongsTo(Workspace::class);
    }
}
