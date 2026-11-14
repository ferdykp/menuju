<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\SoftDeletes;

class Workspace extends Model
{
    use HasFactory;
    use SoftDeletes;

    protected $attributes = ['timezone' => 'Asia/Jakarta'];

    protected $fillable = [
        'owner_id',
        'name',
        'partner_one_name',
        'partner_two_name',
        'wedding_date',
        'timezone',
    ];

    protected function casts(): array
    {
        return ['wedding_date' => 'date', 'onboarding_completed_at' => 'datetime'];
    }

    public function owner(): BelongsTo
    {
        return $this->belongsTo(User::class, 'owner_id');
    }

    public function members(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'workspace_members')
            ->withPivot(['role', 'status'])
            ->withTimestamps();
    }

    public function milestones(): HasMany
    {
        return $this->hasMany(Milestone::class);
    }

    public function invitations(): HasMany
    {
        return $this->hasMany(WorkspaceInvitation::class);
    }

    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    public function guests(): HasMany
    {
        return $this->hasMany(Guest::class);
    }

    public function events(): HasMany { return $this->hasMany(Event::class); }
    public function needs(): HasMany { return $this->hasMany(Need::class); }
    public function needCategories(): HasMany { return $this->hasMany(NeedCategory::class); }
    public function invitation(): HasOne { return $this->hasOne(Invitation::class); }
    public function financialAccounts(): HasMany { return $this->hasMany(FinancialAccount::class); }
    public function financialTransactions(): HasMany { return $this->hasMany(FinancialTransaction::class); }
    public function budgetCategories(): HasMany { return $this->hasMany(BudgetCategory::class); }
    public function savingsGoals(): HasMany { return $this->hasMany(SavingsGoal::class); }
    public function paymentObligations(): HasMany { return $this->hasMany(PaymentObligation::class); }
    public function assets(): HasMany { return $this->hasMany(Asset::class); }
    public function activities(): HasMany { return $this->hasMany(WorkspaceActivity::class); }
    public function decisions(): HasMany { return $this->hasMany(WorkspaceDecision::class); }
    public function prioritySnoozes(): HasMany { return $this->hasMany(HomePrioritySnooze::class); }
}
