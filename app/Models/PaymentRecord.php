<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class PaymentRecord extends Model
{
    protected $fillable = ['payment_obligation_id','financial_transaction_id','amount','paid_at','notes'];
    protected function casts(): array { return ['amount'=>'decimal:2','paid_at'=>'date']; }
    public function obligation(): BelongsTo { return $this->belongsTo(PaymentObligation::class,'payment_obligation_id'); }
    public function transaction(): BelongsTo { return $this->belongsTo(FinancialTransaction::class,'financial_transaction_id'); }
}
