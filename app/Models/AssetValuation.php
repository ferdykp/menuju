<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class AssetValuation extends Model
{
    protected $fillable = ['asset_id', 'recorded_by', 'estimated_value', 'valuation_date', 'notes'];
    protected function casts(): array { return ['estimated_value' => 'decimal:2', 'valuation_date' => 'date']; }
    public function asset(): BelongsTo { return $this->belongsTo(Asset::class); }
    public function recorder(): BelongsTo { return $this->belongsTo(User::class, 'recorded_by'); }
}
