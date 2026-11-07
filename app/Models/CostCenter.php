<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;

class CostCenter extends Model
{
    protected $fillable = [
        'code',
        'name',
        'name_bn',
        'location',
        'division_or_zone',
        'contact_person',
        'phone',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

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

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }
}
