<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class SavingsGoal extends Model
{
    protected $fillable = ['workspace_id','created_by','name','target_amount','target_date','notes','is_archived'];
    protected function casts(): array { return ['target_amount'=>'decimal:2','target_date'=>'date','is_archived'=>'boolean']; }
    public function workspace(): BelongsTo { return $this->belongsTo(Workspace::class); }
    public function deposits(): HasMany { return $this->hasMany(SavingsDeposit::class)->whereNull('reversed_at')->orderByDesc('deposited_at'); }
}
