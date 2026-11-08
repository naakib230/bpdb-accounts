<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    public const TB_TYPES = [
        'revenue_generation'   => ['name_en' => 'Revenue Generation', 'name_bn' => 'রাজস্ব (উৎপাদন)', 'badge' => 'primary'],
        'revenue_distribution' => ['name_en' => 'Revenue Distribution', 'name_bn' => 'রাজস্ব (বিতরণ)', 'badge' => 'info'],
        'overhead'             => ['name_en' => 'Overhead', 'name_bn' => 'উপরিখাত / প্রশাসনিক', 'badge' => 'secondary'],
        'deposit_works'        => ['name_en' => 'Deposit Works', 'name_bn' => 'আমানতি কাজ', 'badge' => 'warning'],
        'development'          => ['name_en' => 'Development', 'name_bn' => 'উন্নয়নমূলক প্রকল্প', 'badge' => 'success'],
    ];

    protected $fillable = [
        'regional_accounts_office_id',
        'code',
        'name',
        'name_bn',
        'trial_balance_type',
        'location',
        'division_or_zone',
        'contact_person',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function regionalAccountsOffice(): BelongsTo
    {
        return $this->belongsTo(RegionalAccountsOffice::class);
    }

    public function users(): BelongsToMany
    {
        return $this->belongsToMany(User::class, 'user_cost_centers')
            ->withTimestamps();
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function vouchers(): HasMany
    {
        return $this->hasMany(Voucher::class);
    }

    public function budgets(): HasMany
    {
        return $this->hasMany(Budget::class);
    }

    public function voucherEntries(): HasMany
    {
        return $this->hasMany(VoucherEntry::class);
    }

    public function getFormattedNameAttribute(): string
    {
        return "{$this->code} - {$this->name_bn} ({$this->name})";
    }

    public function getTrialBalanceTypeLabelAttribute(): string
    {
        return self::TB_TYPES[$this->trial_balance_type]['name_bn'] ?? ($this->trial_balance_type ?: 'উপরিখাত');
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
