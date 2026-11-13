<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class FinancialAccount extends Model
{
    protected $fillable = ['workspace_id', 'created_by', 'name', 'account_type', 'institution', 'opening_balance', 'currency', 'is_archived'];
    protected function casts(): array { return ['opening_balance' => 'decimal:2', 'is_archived' => 'boolean']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function transactions(): HasMany { return $this->hasMany(FinancialTransaction::class, 'account_id'); }
}
