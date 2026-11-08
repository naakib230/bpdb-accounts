<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\HasMany;

class RegionalAccountsOffice extends Model
{
    protected $fillable = [
        'code',
        'name',
        'name_bn',
        'address',
        'type',
        'is_active',
    ];

    protected $casts = [
        'is_active' => 'boolean',
    ];

    public function costCenters(): HasMany
    {
        return $this->hasMany(CostCenter::class);
    }

    public function bankAccounts(): HasMany
    {
        return $this->hasMany(BankAccount::class);
    }

    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function getFormattedNameAttribute(): string
    {
        return "{$this->code} - {$this->name_bn} ({$this->name})";
    }
}
