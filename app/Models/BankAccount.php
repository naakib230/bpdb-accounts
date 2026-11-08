<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BankAccount extends Model
{
    protected $fillable = [
        'regional_accounts_office_id',
        'bank_name',
        'branch_name',
        'account_number',
        'account_title',
        'account_type',
        'chart_of_account_id',
        'cost_center_id',
        'opening_balance',
        'current_balance',
        'is_active',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_active'       => 'boolean',
    ];

    public function regionalAccountsOffice(): BelongsTo
    {
        return $this->belongsTo(RegionalAccountsOffice::class);
    }

    public function chartOfAccount(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class);
    }

    public function costCenter(): BelongsTo
    {
        return $this->belongsTo(CostCenter::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_bank_accounts')
            ->withTimestamps();
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function reconciliations(): HasMany
    {
        return $this->hasMany(BankReconciliation::class);
    }

    public function getFormattedTitleAttribute(): string
    {
        return "{$this->bank_name} - {$this->account_number} ({$this->account_title})";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
