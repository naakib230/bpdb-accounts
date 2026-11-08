<?php

namespace App\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;

class User extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $fillable = [
        'username',
        'erp_no',
        'nid_no',
        'name',
        'name_bn',
        'email',
        'phone',
        'designation',
        'designation_code',
        'cadre',
        'post_type',
        'photo_path',
        'office_name',
        'regional_accounts_office_id',
        'password',
        'is_active',
        'last_login_at',
    ];

    protected $hidden = [
        'password',
        'remember_token',
    ];

    protected function casts(): array
    {
        return [
            'is_active'     => 'boolean',
            'last_login_at' => 'datetime',
            'password'      => 'hashed',
        ];
    }

    /**
     * প্রোফাইল ছবির পূর্ণ URL ফেরত দেয়
     */
    public function getPhotoUrlAttribute(): ?string
    {
        if ($this->photo_path && file_exists(public_path($this->photo_path))) {
            return asset($this->photo_path);
        }

        if ($this->erp_no || $this->username) {
            $identifier = $this->erp_no ?: $this->username;
            $matches = glob(public_path('uploads/profiles/' . $identifier . '_*.*'));
            if (! empty($matches) && file_exists($matches[0])) {
                return asset('uploads/profiles/' . basename($matches[0]));
            }
        }

        return null;
    }

    public function regionalAccountsOffice(): \Illuminate\Database\Eloquent\Relations\BelongsTo
    {
        return $this->belongsTo(RegionalAccountsOffice::class);
    }

    public function roles(): BelongsToMany
    {
        return $this->belongsToMany(Role::class, 'user_roles')
            ->withPivot('is_active')
            ->withTimestamps();
    }

    public function costCenters(): BelongsToMany
    {
        return $this->belongsToMany(CostCenter::class, 'user_cost_centers')
            ->withTimestamps();
    }

    public function bankAccounts(): BelongsToMany
    {
        return $this->belongsToMany(BankAccount::class, 'user_bank_accounts')
            ->withTimestamps();
    }

    public function chartOfAccounts(): BelongsToMany
    {
        return $this->belongsToMany(ChartOfAccount::class, 'user_chart_of_accounts')
            ->withTimestamps();
    }

    public function vouchersPrepared(): HasMany
    {
        return $this->hasMany(Voucher::class, 'prepared_by_id');
    }

    public function auditLogs(): HasMany
    {
        return $this->hasMany(AuditLog::class);
    }

    public function hasRole(string $roleName): bool
    {
        return $this->roles->contains('name', $roleName);
    }

    public function isSystemAdmin(): bool
    {
        return $this->hasRole('system_admin') || $this->hasRole('admin');
    }

    public function isDirectorAccounts(): bool
    {
        return $this->hasRole('dir_accounts') || $this->hasRole('director_accounts');
    }

    public function isCentralUser(): bool
    {
        if ($this->isSystemAdmin() || $this->isDirectorAccounts()) {
            return true;
        }

        foreach ($this->roles as $role) {
            if (str_starts_with($role->name, 'bkc_')) {
                return true;
            }
        }

        return false;
    }

    public function activeRole(): ?Role
    {
        $roleName = session('active_role');
        if ($roleName) {
            $role = $this->roles->firstWhere('name', $roleName);
            if ($role) {
                return $role;
            }
        }
        return $this->roles->first();
    }

    public function switchRole(string $roleName): bool
    {
        if ($this->hasRole($roleName)) {
            session(['active_role' => $roleName]);
            return true;
        }
        return false;
    }

    public function canAccessCostCenter(int $costCenterId): bool
    {
        if ($this->isCentralUser()) {
            return true;
        }

        if ($this->costCenters->contains('id', $costCenterId)) {
            return true;
        }

        if ($this->regional_accounts_office_id) {
            $cc = CostCenter::find($costCenterId);
            if ($cc && $cc->regional_accounts_office_id === $this->regional_accounts_office_id) {
                return true;
            }
        }

        return false;
    }

    public function canAccessBankAccount(int $bankAccountId): bool
    {
        if ($this->isCentralUser()) {
            return true;
        }

        if ($this->bankAccounts->contains('id', $bankAccountId)) {
            return true;
        }

        if ($this->regional_accounts_office_id) {
            $ba = BankAccount::find($bankAccountId);
            if ($ba && $ba->regional_accounts_office_id === $this->regional_accounts_office_id) {
                return true;
            }
        }

        return false;
    }

    public function canAccessChartOfAccount(int $coaId): bool
    {
        if ($this->isCentralUser()) {
            return true;
        }
        if ($this->chartOfAccounts()->count() === 0) {
            return true; // if none assigned, allow general COA
        }
        return $this->chartOfAccounts->contains('id', $coaId);
    }
}
