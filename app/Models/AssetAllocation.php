<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetAllocation extends Model
{
    protected $fillable = ['asset_id', 'need_id', 'created_by', 'quantity', 'notes'];
    protected function casts(): array { return ['quantity' => 'decimal:3']; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function need(): BelongsTo { return $this->belongsTo(Need::class); }
    public function creator(): BelongsTo { return $this->belongsTo(User::class, 'created_by'); }
}
