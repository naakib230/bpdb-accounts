<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;

class ChartOfAccount extends Model
{
    protected $fillable = [
        'account_code',
        'account_name',
        'account_name_bn',
        'account_type',
        'parent_id',
        'normal_balance',
        'opening_balance',
        'current_balance',
        'is_leaf',
        'is_active',
        'description',
    ];

    protected $casts = [
        'opening_balance' => 'decimal:2',
        'current_balance' => 'decimal:2',
        'is_leaf'         => 'boolean',
        'is_active'       => 'boolean',
    ];

    public function parent(): BelongsTo
    {
        return $this->belongsTo(ChartOfAccount::class, 'parent_id');
    }

    public function children(): HasMany
    {
        return $this->hasMany(ChartOfAccount::class, 'parent_id');
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_chart_of_accounts')
            ->withTimestamps();
    }

    public function bankAccount(): HasOne
    {
        return $this->hasOne(BankAccount::class);
    }

    public function voucherEntries(): HasMany
    {
        return $this->hasMany(VoucherEntry::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function getFormattedNameAttribute(): string
    {
        return "{$this->account_code} - {$this->account_name_bn} ({$this->account_name})";
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeLeaf($query)
    {
        return $query->where('is_leaf', true);
    }

    public function scopeType($query, string $type)
    {
        return $query->where('account_type', $type);
    }
}
