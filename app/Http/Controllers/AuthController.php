<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\ChartOfAccount;
use App\Models\CostCenter;
use App\Models\Role;
use App\Models\User;
use App\Services\BpdbErpAuthService;
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

    public function login(Request $request, BpdbErpAuthService $erpAuthService): RedirectResponse
    {
        $credentials = $request->validate([
            'identifier' => ['required', 'string', 'max:191'],
            'password'   => ['required', 'string'],
        ], [
            'identifier.required' => 'অনুগ্রহ করে ERP নম্বর অথবা জাতীয় পরিচয়পত্র নম্বর প্রদান করুন।',
            'identifier.string'   => 'ERP নম্বর অথবা জাতীয় পরিচয়পত্র নম্বর সঠিকভাবে প্রদান করুন।',
            'password.required'   => 'অনুগ্রহ করে পাসওয়ার্ড প্রদান করুন।',
            'password.string'     => 'পাসওয়ার্ড সঠিকভাবে প্রদান করুন।',
        ]);

        $identifier = trim($credentials['identifier']);

        // ১. ডাটাবেজে ইউজার খোঁজা (username, erp_no, অথবা nid_no দিয়ে)
        $user = User::with('roles')
            ->where('username', $identifier)
            ->orWhere('erp_no', $identifier)
            ->orWhere('nid_no', $identifier)
            ->first();

        // ২. যদি লোকাল ইউজার পাওয়া না যায়
        if (! $user) {
            // ERP OData এ চেক করা যে এই কর্মকর্তা বিউবোর বৈধ কর্মকর্তা কি না
            $erpRecord = $erpAuthService->fetchEmployeeFromErp($identifier);
            if ($erpRecord) {
                $erpNo = $erpRecord['No'] ?? $identifier;
                $desigCode = $erpRecord['Designation_Code'] ?? null;

                // পদবী অনুমোদন পরীক্ষা
                if (! $erpAuthService->isSystemAdmin($erpNo) && ! $erpAuthService->isAllowedDesignation($desigCode)) {
                    throw ValidationException::withMessages([
                        'identifier' => 'দুঃখিত, শুধুমাত্র বিউবো অর্থ ও হিসাব ক্যাডারের অনুমোদিত পদবীধারী (হিসাব শাখা) কর্মকর্তাবৃন্দ এবং সিস্টেম অ্যাডমিনিস্ট্রেটরগণ এই পোর্টালে লগইন করতে পারবেন। আপনার বর্তমান পদবী অনুমোদিত তালিকায় নেই।',
                    ]);
                }

                throw ValidationException::withMessages([
                    'identifier' => 'আপনার অ্যাকাউন্টটি এখনও সক্রিয় করা হয়নি। অনুগ্রহ করে নিচের "প্রথমবার অ্যাকাউন্ট অ্যাক্টিভেশন" বাটনে ক্লিক করে পাসওয়ার্ড নির্ধারণ করুন।',
                ]);
            }

            throw ValidationException::withMessages([
                'identifier' => 'আপনার ERP/NID অথবা পাসওয়ার্ড সঠিক নয়। অনুগ্রহ করে পুনরায় চেষ্টা করুন।',
            ]);
        }

        // ৩. পাসওয়ার্ড যাচাই
        if (! Hash::check($credentials['password'], $user->password)) {
            throw ValidationException::withMessages([
                'identifier' => 'আপনার ERP/NID অথবা পাসওয়ার্ড সঠিক নয়। অনুগ্রহ করে পুনরায় চেষ্টা করুন।',
            ]);
        }

        // ৪. অ্যাকাউন্ট সক্রিয়তা যাচাই
        if (! $user->is_active) {
            throw ValidationException::withMessages([
                'identifier' => 'আপনার অ্যাকাউন্টটি নিষ্ক্রিয় রয়েছে। অনুগ্রহ করে সিস্টেম অ্যাডমিনিস্ট্রেটরের সাথে যোগাযোগ করুন।',
            ]);
        }

        // ৫. অনুমোদিত পদবী ও সিস্টেম অ্যাডমিন যাচাই (কঠোর ফিল্টারিং)
        $erpAuthService->verifyEligibility($user);

        // ৬. সফল লগইন সেশন পরিচালনা
        Auth::login($user, $request->boolean('remember'));
        $request->session()->regenerate();

        $activeRole = $user->roles->first()?->name ?? 'accountant';
        session(['active_role' => $activeRole]);

        $user->update(['last_login_at' => now()]);

        AuditLog::log('LOGIN', 'ব্যবহারকারী সিস্টেমে সফলভাবে লগইন করেছেন।');

        return redirect()->intended(route('dashboard'))
            ->with('success', 'স্বাগতম, ' . ($user->name_bn ?: $user->name) . '! আপনি সফলভাবে প্রবেশ করেছেন।');
    }

    public function showFirstTime(): View|RedirectResponse
    {
        if (Auth::check()) {
            return redirect()->route('dashboard');
        }
        return view('auth.first_time');
    }

    public function verifyFirstTime(Request $request, BpdbErpAuthService $erpAuthService): RedirectResponse
    {
        $data = $request->validate([
            'identifier' => ['required', 'string', 'max:100'],
        ], [
            'identifier.required' => 'অনুগ্রহ করে ERP নম্বর অথবা জাতীয় পরিচয়পত্র নম্বর প্রদান করুন।',
        ]);

        $identifier = trim($data['identifier']);

        // ইতিমধ্যে সক্রিয় অ্যাকাউন্ট আছে কি না চেক
        $existing = User::where('username', $identifier)
            ->orWhere('erp_no', $identifier)
            ->orWhere('nid_no', $identifier)
            ->first();

        if ($existing && $existing->is_active) {
            return redirect()->route('login')
                ->with('warning', 'আপনার অ্যাকাউন্টটি ইতিমধ্যে সক্রিয় রয়েছে। অনুগ্রহ করে পাসওয়ার্ড দিয়ে সরাসরি লগইন করুন।');
        }

        // ERP OData এ কর্মকর্তা খোঁজা
        $record = $erpAuthService->fetchEmployeeFromErp($identifier);
        if (! $record) {
            throw ValidationException::withMessages([
                'identifier' => 'BPDB ERP সার্ভার হতে আপনার কর্মকর্তা/কর্মচারী তথ্য পাওয়া যায়নি। অনুগ্রহ করে সঠিক ERP বা NID নম্বর দিন।',
            ]);
        }

        $erpNo = trim((string) ($record['No'] ?? ''));
        $desigCode = trim((string) ($record['Designation_Code'] ?? ''));

        // পদবী বা সিস্টেম অ্যাডমিন অনুমোদন যাচাই
        if (! $erpAuthService->isSystemAdmin($erpNo) && ! $erpAuthService->isAllowedDesignation($desigCode)) {
            $desigName = $record['Designation'] ?? 'অজানা পদবী';
            throw ValidationException::withMessages([
                'identifier' => "দুঃখিত, শুধুমাত্র বিউবো অর্থ ও হিসাব ক্যাডারের অনুমোদিত ১২টি পদবীধারী (হিসাব শাখা) কর্মকর্তা/কর্মচারীগণ এই পোর্টালে যুক্ত হতে পারবেন। আপনার বর্তমান পদবী '{$desigName}' (কোড: {$desigCode}) অনুমোদিত নয়।",
            ]);
        }

        $desigInfo = BpdbErpAuthService::ALLOWED_DESIGNATIONS[$desigCode] ?? null;

        $verification = [
            'erp_no'           => $erpNo,
            'nid_no'           => trim((string) ($record['NID'] ?? '')),
            'name_en'          => trim((string) ($record['Name'] ?? ($record['First_Name'] ?? ''))),
            'name_bn'          => trim((string) ($record['First_Name'] ?? ($record['Name'] ?? ''))),
            'designation_code' => $desigCode,
            'designation'      => $desigInfo['name_bn'] ?? ($record['Designation'] ?? 'কর্মকর্তা'),
            'office_name'      => trim((string) ($record['Office_Name'] ?? ($record['Posting_Location'] ?? 'বিউবো'))),
            'post_type'        => trim((string) ($record['Post_Type'] ?? 'Officer')),
            'cadre'            => 'Finance Cadre',
        ];

        session(['first_time_verification' => $verification]);

        return redirect()->route('first_time')->with('verified', true);
    }

    public function activateAccount(Request $request, BpdbErpAuthService $erpAuthService): RedirectResponse
    {
        $verification = session('first_time_verification');
        if (! $verification || empty($verification['erp_no'])) {
            return redirect()->route('first_time')->with('error', 'অ্যাক্টিভেশন সেশনের মেয়াদ শেষ হয়েছে। অনুগ্রহ করে আবার পরিচয় যাচাই করুন।');
        }

        $data = $request->validate([
            'password' => ['required', 'string', 'min:6', 'confirmed'],
        ], [
            'password.required'  => 'অনুগ্রহ করে নতুন পাসওয়ার্ড প্রদান করুন।',
            'password.min'       => 'পাসওয়ার্ড কমপক্ষে ৬ অক্ষরের হতে হবে।',
            'password.confirmed' => 'পাসওয়ার্ড নিশ্চিতকরণ মিলছে না।',
        ]);

        $erpNo = $verification['erp_no'];
        $desigCode = $verification['designation_code'] ?? null;

        // পুনরায় পদবী অনুমতি চেক
        if (! $erpAuthService->isSystemAdmin($erpNo) && ! $erpAuthService->isAllowedDesignation($desigCode)) {
            session()->forget('first_time_verification');
            return redirect()->route('login')->with('error', 'অননুমোদিত পদবী।');
        }

        $roleName = $erpAuthService->getDefaultRole($erpNo, $desigCode);
        $role = Role::where('name', $roleName)->first() ?? Role::where('name', 'accountant')->first();

        // অ্যাকাউন্ট তৈরি বা আপডেট
        $user = User::updateOrCreate(
            ['username' => $erpNo],
            [
                'erp_no'           => $erpNo,
                'nid_no'           => $verification['nid_no'] ?: null,
                'name'             => $verification['name_en'] ?: $erpNo,
                'name_bn'          => $verification['name_bn'] ?: $erpNo,
                'designation'      => $verification['designation'],
                'designation_code' => $desigCode,
                'office_name'      => $verification['office_name'],
                'cadre'            => 'Finance Cadre',
                'post_type'        => $verification['post_type'],
                'password'         => Hash::make($data['password']),
                'is_active'        => true,
            ]
        );

        if ($role) {
            $user->roles()->sync([$role->id]);
        }

        // কস্ট সেন্টার এবং চার্ট অব অ্যাকাউন্টস ডিফল্ট এসাইন
        $allCostCenters = CostCenter::all();
        $user->costCenters()->sync($allCostCenters->pluck('id'));
        $allLeafCoa = ChartOfAccount::leaf()->get();
        $user->chartOfAccounts()->sync($allLeafCoa->pluck('id'));

        session()->forget('first_time_verification');

        AuditLog::log('USER_ACTIVATED', "ব্যবহারকারী {$user->username} প্রথমবার অ্যাকাউন্ট সক্রিয় করেছেন।", $user);

        return redirect()->route('login')
            ->with('success', 'আপনার অ্যাকাউন্ট সফলভাবে সক্রিয় করা হয়েছে! এখন আপনার ERP নম্বর ও নতুন পাসওয়ার্ড দিয়ে লগইন করুন।');
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
            'password' => ['nullable', 'string', 'min:6'],
        ]);

        $updateData = [
            'name'    => $data['name'],
            'name_bn' => $data['name_bn'],
            'email'   => $data['email'],
            'phone'   => $data['phone'],
        ];

        if (! empty($data['password'])) {
            $updateData['password'] = Hash::make($data['password']);
        }

        $user->update($updateData);

        AuditLog::log('PROFILE_UPDATE', 'ব্যবহারকারী প্রোফাইল হালনাগাদ করেছেন।');

        return back()->with('success', 'প্রোফাইল সফলভাবে হালনাগাদ করা হয়েছে।');
    }
}
