<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class GuestInvitationToken extends Model
{
    public $timestamps = false;
    protected $fillable = ['invitation_id', 'guest_id', 'token_hash', 'last_sent_at'];
    protected function casts(): array { return ['last_sent_at' => 'datetime']; }
    public function invitation(): BelongsTo { return $this->belongsTo(Invitation::class); }
    public function guest(): BelongsTo { return $this->belongsTo(Guest::class); }
}
