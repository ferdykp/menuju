<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class SavingsDeposit extends Model
{
    protected $fillable = ['savings_goal_id','financial_transaction_id','amount','deposited_at','notes','reversed_at'];
    protected function casts(): array { return ['amount'=>'decimal:2','deposited_at'=>'date','reversed_at'=>'datetime']; }
    public function goal(): BelongsTo { return $this->belongsTo(SavingsGoal::class,'savings_goal_id'); }
    public function transaction(): BelongsTo { return $this->belongsTo(FinancialTransaction::class,'financial_transaction_id'); }
}
