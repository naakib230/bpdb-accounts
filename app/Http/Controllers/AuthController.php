<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Validation\ValidationException;
use Illuminate\View\View;

class AuthController extends Controller
{
    public function showLogin(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.login');
    }

    public function login(Request $request): RedirectResponse
    {
        $credentials = $request->validate([
            'username' => ['required', 'string'],
            'password' => ['required', 'string'],
        ], [
            'username.required' => 'অনুগ্রহ করে ইউজারনেম অথবা ERP নম্বর প্রদান করুন।',
            'password.required' => 'অনুগ্রহ করে পাসওয়ার্ড প্রদান করুন।',
        ]);

        $user = User::with('roles')
            ->where('username', $credentials['username'])
            ->orWhere('email', $credentials['username'])
            ->first();

        if (! $user || ! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'username' => 'প্রদত্ত ইউজারনেম/ইআরপি অথবা পাসওয়ার্ড সঠিক নয়।',
            ]);
        }

        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'username' => 'আপনার অ্যাকাউন্টটি নিষ্ক্রিয় রয়েছে। অনুগ্রহ করে অ্যাডমিনের সাথে যোগাযোগ করুন।',
            ]);
        }

        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $activeRole = $user->roles->first()?->name ?? 'accountant';
        session(['active_role' => $activeRole]);

        $user->update(['last_login_at' => now()]);

        AuditLog::log('LOGIN', 'ব্যবহারকারী সিস্টেমে সফলভাবে লগইন করেছেন।');

        return redirect()->intended(route('dashboard'))
            ->with('success', 'স্বাগতম, ' . $user->name . '! আপনি সফলভাবে প্রবেশ করেছেন।');
    }

    public function switchRole(Request $request): RedirectResponse
    {
        $request->validate(['role' => ['required', 'string']]);
        $roleName = $request->input('role');

        $user = Auth::user();
        if ($user && $user->switchRole($roleName)) {
            AuditLog::log('SWITCH_ROLE', "ভূমিকা পরিবর্তন করে '{$roleName}' করা হয়েছে।");
            return back()->with('success', 'সফলভাবে ভূমিকা পরিবর্তন করা হয়েছে।');
        }

        return back()->with('error', 'অননুমোদিত ভূমিকা পরিবর্তন প্রচেষ্টা।');
    }

    public function logout(Request $request): RedirectResponse
    {
        if (Auth::check()) {
            AuditLog::log('LOGOUT', 'ব্যবহারকারী সিস্টেম হতে লগআউট করেছেন।');
        }

        Auth::logout();
        $request->session()->invalidate();
        $request->session()->regenerateToken();

        return redirect()->route('login')->with('info', 'আপনি সফলভাবে লগআউট করেছেন।');
    }

    public function showProfile(): View
    {
        $user = Auth::user()->load(['roles', 'costCenters', 'bankAccounts', 'chartOfAccounts']);
        return view('auth.profile', compact('user'));
    }

    public function updateProfile(Request $request): RedirectResponse
    {
        $user = Auth::user();
        $data = $request->validate([
            'name'     => ['required', 'string', 'max:191'],
            'name_bn'  => ['nullable', 'string', 'max:191'],
            'email'    => ['nullable', 'email', 'max:191', 'unique:users,email,' . $user->id],
            'phone'    => ['nullable', 'string', 'max:50'],
            'password' => ['nullable', 'string', 'min:6', 'confirmed'],
        ]);

        $updateData = [
            'name'    => $data['name'],
            'name_bn' => $data['name_bn'] ?? $user->name_bn,
            'email'   => $data['email'] ?? $user->email,
            'phone'   => $data['phone'] ?? $user->phone,
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);
        AuditLog::log('UPDATE_PROFILE', 'প্রোফাইল তথ্য আপডেট করা হয়েছে।');

        return back()->with('success', 'আপনার প্রোফাইল সফলভাবে আপডেট করা হয়েছে।');
    }
}
