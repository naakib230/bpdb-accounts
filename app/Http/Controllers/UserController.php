<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\RegionalAccountsOffice;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(Request $request): View
    {
        $roleFilter = $request->input('role');
        $raoFilter  = $request->input('rao_id');
        $search     = $request->input('search');

        $query = User::with(['roles', 'costCenters', 'bankAccounts', 'regionalAccountsOffice'])->orderBy('name');

        if ($roleFilter) {
            $query->whereHas('roles', fn($q) => $q->where('name', $roleFilter));
        }
        if ($raoFilter) {
            $query->where('regional_accounts_office_id', $raoFilter);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('name', 'like', "%{$search}%")
                  ->orWhere('name_bn', 'like', "%{$search}%")
                  ->orWhere('username', 'like', "%{$search}%")
                  ->orWhere('email', 'like', "%{$search}%");
            });
        }

        $users = $query->paginate(15)->withQueryString();
        $roles = Role::orderBy('name')->get();
        $raos  = RegionalAccountsOffice::active()->orderBy('code')->get();
        $costCenters = CostCenter::active()->orderBy('name_bn')->get();
        $bankAccounts = BankAccount::active()->orderBy('bank_name')->get();
        $accounts = ChartOfAccount::leaf()->orderBy('account_code')->get();

        return view('users.index', compact('users', 'roles', 'raos', 'costCenters', 'bankAccounts', 'accounts', 'roleFilter', 'raoFilter', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username'                    => ['required', 'string', 'max:100', 'unique:users,username'],
            'name'                        => ['required', 'string', 'max:191'],
            'name_bn'                     => ['nullable', 'string', 'max:191'],
            'email'                       => ['nullable', 'email', 'max:191', 'unique:users,email'],
            'phone'                       => ['nullable', 'string', 'max:50'],
            'designation'                 => ['nullable', 'string', 'max:191'],
            'office_name'                 => ['nullable', 'string', 'max:255'],
            'regional_accounts_office_id' => ['nullable', 'exists:regional_accounts_offices,id'],
            'password'                    => ['required', 'string', 'min:6'],
            'role_ids'                    => ['required', 'array'],
            'role_ids.*'                  => ['exists:roles,id'],
            'cost_center_ids'             => ['nullable', 'array'],
            'cost_center_ids.*'           => ['exists:cost_centers,id'],
            'bank_account_ids'            => ['nullable', 'array'],
            'bank_account_ids.*'          => ['exists:bank_accounts,id'],
        ]);

        $user = User::create([
            'username'                    => $data['username'],
            'name'                        => $data['name'],
            'name_bn'                     => $data['name_bn'] ?? $data['name'],
            'email'                       => $data['email'] ?? null,
            'phone'                       => $data['phone'] ?? null,
            'designation'                 => $data['designation'] ?? null,
            'office_name'                 => $data['office_name'] ?? null,
            'regional_accounts_office_id' => $data['regional_accounts_office_id'] ?? null,
            'password'                    => Hash::make($data['password']),
            'is_active'                   => true,
        ]);

        $user->roles()->sync($data['role_ids']);

        if (! empty($data['cost_center_ids'])) {
            $user->costCenters()->sync($data['cost_center_ids']);
        }

        if (! empty($data['bank_account_ids'])) {
            $user->bankAccounts()->sync($data['bank_account_ids']);
        }

        AuditLog::log('CREATE_USER', "নতুন ব্যবহারকারী '{$user->name} ({$user->username})' তৈরি করা হয়েছে।");

        return back()->with('success', 'নতুন ব্যবহারকারী সফলভাবে তৈরি করা হয়েছে।');
    }

    public function update(Request $request, User $user): RedirectResponse
    {
        $data = $request->validate([
            'username'                    => ['required', 'string', 'max:100', 'unique:users,username,' . $user->id],
            'name'                        => ['required', 'string', 'max:191'],
            'name_bn'                     => ['nullable', 'string', 'max:191'],
            'email'                       => ['nullable', 'email', 'max:191', 'unique:users,email,' . $user->id],
            'phone'                       => ['nullable', 'string', 'max:50'],
            'designation'                 => ['nullable', 'string', 'max:191'],
            'office_name'                 => ['nullable', 'string', 'max:255'],
            'regional_accounts_office_id' => ['nullable', 'exists:regional_accounts_offices,id'],
            'password'                    => ['nullable', 'string', 'min:6'],
            'role_ids'                    => ['required', 'array'],
            'role_ids.*'                  => ['exists:roles,id'],
            'is_active'                   => ['nullable', 'boolean'],
            'cost_center_ids'             => ['nullable', 'array'],
            'cost_center_ids.*'           => ['exists:cost_centers,id'],
            'bank_account_ids'            => ['nullable', 'array'],
            'bank_account_ids.*'          => ['exists:bank_accounts,id'],
        ]);

        $updateData = [
            'username'                    => $data['username'],
            'name'                        => $data['name'],
            'name_bn'                     => $data['name_bn'] ?? $user->name_bn,
            'email'                       => $data['email'] ?? $user->email,
            'phone'                       => $data['phone'] ?? $user->phone,
            'designation'                 => $data['designation'] ?? $user->designation,
            'office_name'                 => $data['office_name'] ?? $user->office_name,
            'regional_accounts_office_id' => $data['regional_accounts_office_id'] ?? null,
            'is_active'                   => $request->boolean('is_active', true),
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        $user->roles()->sync($data['role_ids']);
        $user->costCenters()->sync($data['cost_center_ids'] ?? []);
        $user->bankAccounts()->sync($data['bank_account_ids'] ?? []);

        AuditLog::log('UPDATE_USER', "ব্যবহারকারী '{$user->name}' এর ভূমিকা ও তথ্য আপডেট করা হয়েছে।");

        return back()->with('success', 'ব্যবহারকারীর তথ্য সফলভাবে আপডেট করা হয়েছে।');
    }

    public function assignScope(Request $request, User $user): RedirectResponse
    {
        $request->validate([
            'cost_center_ids'   => ['nullable', 'array'],
            'bank_account_ids'  => ['nullable', 'array'],
            'chart_of_account_ids' => ['nullable', 'array'],
        ]);

        $user->costCenters()->sync($request->input('cost_center_ids', []));
        $user->bankAccounts()->sync($request->input('bank_account_ids', []));
        $user->chartOfAccounts()->sync($request->input('chart_of_account_ids', []));

        AuditLog::log('ASSIGN_USER_SCOPE', "ব্যবহারকারী '{$user->username}' এর কস্ট সেন্টার, ব্যাংক ও হিসাব খাত সুযোগ আপডেট করা হয়েছে।");

        return back()->with('success', 'ব্যবহারকারীর সুযোগ (Scope) সফলভাবে সংরক্ষিত হয়েছে।');
    }
}
