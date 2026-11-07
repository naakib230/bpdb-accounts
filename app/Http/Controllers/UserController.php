<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\BankAccount;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\Role;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Hash;
use Illuminate\View\View;

class UserController extends Controller
{
    public function index(): View
    {
        $users = User::with(['roles', 'costCenters', 'bankAccounts'])->orderBy('name')->paginate(15);
        $roles = Role::all();
        $costCenters = CostCenter::active()->orderBy('name_bn')->get();
        $bankAccounts = BankAccount::active()->orderBy('bank_name')->get();
        $accounts = ChartOfAccount::leaf()->orderBy('account_code')->get();

        return view('users.index', compact('users', 'roles', 'costCenters', 'bankAccounts', 'accounts'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'username'    => ['required', 'string', 'max:100', 'unique:users,username'],
            'name'        => ['required', 'string', 'max:191'],
            'name_bn'     => ['nullable', 'string', 'max:191'],
            'email'       => ['nullable', 'email', 'max:191', 'unique:users,email'],
            'phone'       => ['nullable', 'string', 'max:50'],
            'designation' => ['nullable', 'string', 'max:191'],
            'office_name' => ['nullable', 'string', 'max:255'],
            'password'    => ['required', 'string', 'min:6'],
            'role_id'     => ['required', 'exists:roles,id'],
            'cost_center_ids' => ['nullable', 'array'],
            'bank_account_ids'=> ['nullable', 'array'],
        ]);

        $user = User::create([
            'username'    => $data['username'],
            'name'        => $data['name'],
            'name_bn'     => $data['name_bn'] ?? $data['name'],
            'email'       => $data['email'] ?? null,
            'phone'       => $data['phone'] ?? null,
            'designation' => $data['designation'] ?? null,
            'office_name' => $data['office_name'] ?? null,
            'password'    => Hash::make($data['password']),
            'is_active'   => true,
        ]);

        $user->roles()->sync([$data['role_id']]);

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
            'username'    => ['required', 'string', 'max:100', 'unique:users,username,' . $user->id],
            'name'        => ['required', 'string', 'max:191'],
            'name_bn'     => ['nullable', 'string', 'max:191'],
            'email'       => ['nullable', 'email', 'max:191', 'unique:users,email,' . $user->id],
            'phone'       => ['nullable', 'string', 'max:50'],
            'designation' => ['nullable', 'string', 'max:191'],
            'office_name' => ['nullable', 'string', 'max:255'],
            'password'    => ['nullable', 'string', 'min:6'],
            'role_id'     => ['required', 'exists:roles,id'],
            'is_active'   => ['nullable', 'boolean'],
            'cost_center_ids' => ['nullable', 'array'],
            'bank_account_ids'=> ['nullable', 'array'],
        ]);

        $updateData = [
            'username'    => $data['username'],
            'name'        => $data['name'],
            'name_bn'     => $data['name_bn'] ?? $user->name_bn,
            'email'       => $data['email'] ?? $user->email,
            'phone'       => $data['phone'] ?? $user->phone,
            'designation' => $data['designation'] ?? $user->designation,
            'office_name' => $data['office_name'] ?? $user->office_name,
            'is_active'   => $request->boolean('is_active', true),
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        $user->roles()->sync([$data['role_id']]);

        $user->costCenters()->sync($data['cost_center_ids'] ?? []);
        $user->bankAccounts()->sync($data['bank_account_ids'] ?? []);

        AuditLog::log('UPDATE_USER', "ব্যবহারকারী '{$user->name}' এর তথ্য আপডেট করা হয়েছে।");

        return back()->with('success', 'ব্যবহারকারীর তথ্য সফলভাবে আপডেট হয়েছে।');
    }

    public function toggleActive(User $user): RedirectResponse
    {
        if ($user->id === Auth::id()) {
            return back()->with('error', 'নিজের অ্যাকাউন্ট নিষ্ক্রিয় করা সম্ভব নয়।');
        }

        $user->is_active = ! $user->is_active;
        $user->save();

        $statusStr = $user->is_active ? 'সক্রিয়' : 'নিষ্ক্রিয়';
        AuditLog::log('TOGGLE_USER_STATUS', "ব্যবহারকারী '{$user->username}' এর অবস্থা {$statusStr} করা হয়েছে।");

        return back()->with('success', "ব্যবহারকারী '{$user->name}' সফলভাবে {$statusStr} করা হয়েছে।");
    }
}
