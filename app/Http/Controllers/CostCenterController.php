<?php

namespace App\Http\Controllers;

use App\Models\AuditLog;
use App\Models\CostCenter;
use App\Models\User;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\Routing\Controller;
use Illuminate\View\View;
use Symfony\Component\HttpFoundation\StreamedResponse;

class CostCenterController extends Controller
{
    public function index(Request $request): View
    {
        $raoId  = $request->input('rao_id');
        $tbType = $request->input('tb_type');
        $search = $request->input('search');

        $query = CostCenter::with(['users', 'regionalAccountsOffice'])->orderBy('code');

        if ($raoId) {
            $query->where('regional_accounts_office_id', $raoId);
        }
        if ($tbType) {
            $query->where('trial_balance_type', $tbType);
        }
        if ($search) {
            $query->where(function ($q) use ($search) {
                $q->where('code', 'like', "%{$search}%")
                  ->orWhere('name', 'like', "%{$search}%")
                  ->orWhere('name_bn', 'like', "%{$search}%");
            });
        }

        $costCenters = $query->paginate(15)->withQueryString();
        $users = User::where('is_active', true)->orderBy('name')->get();
        $raos = \App\Models\RegionalAccountsOffice::active()->orderBy('code')->get();

        return view('cost_centers.index', compact('costCenters', 'users', 'raos', 'raoId', 'tbType', 'search'));
    }

    public function store(Request $request): RedirectResponse
    {
        $data = $request->validate([
            'regional_accounts_office_id' => ['nullable', 'exists:regional_accounts_offices,id'],
            'trial_balance_type'          => ['required', 'string', 'in:revenue_generation,revenue_distribution,overhead,deposit_works,development'],
            'code'                        => ['required', 'string', 'max:50', 'unique:cost_centers,code'],
            'name'                        => ['required', 'string', 'max:191'],
            'name_bn'                     => ['required', 'string', 'max:191'],
            'location'                    => ['nullable', 'string', 'max:100'],
            'division_or_zone'            => ['nullable', 'string', 'max:100'],
            'contact_person'              => ['nullable', 'string', 'max:100'],
            'phone'                       => ['nullable', 'string', 'max:50'],
        ]);

        $cc = CostCenter::create($data);
        AuditLog::log('CREATE_COST_CENTER', "নতুন কস্ট সেন্টার '{$cc->name_bn} ({$cc->code})' তৈরি করা হয়েছে।");

        return back()->with('success', 'কস্ট সেন্টার সফলভাবে তৈরি করা হয়েছে।');
    }

    public function update(Request $request, CostCenter $costCenter): RedirectResponse
    {
        $data = $request->validate([
            'regional_accounts_office_id' => ['nullable', 'exists:regional_accounts_offices,id'],
            'trial_balance_type'          => ['required', 'string', 'in:revenue_generation,revenue_distribution,overhead,deposit_works,development'],
            'code'                        => ['required', 'string', 'max:50', 'unique:cost_centers,code,' . $costCenter->id],
            'name'                        => ['required', 'string', 'max:191'],
            'name_bn'                     => ['required', 'string', 'max:191'],
            'location'                    => ['nullable', 'string', 'max:100'],
            'division_or_zone'            => ['nullable', 'string', 'max:100'],
            'contact_person'              => ['nullable', 'string', 'max:100'],
            'phone'                       => ['nullable', 'string', 'max:50'],
            'is_active'                   => ['nullable', 'boolean'],
        ]);

        $data['is_active'] = $request->boolean('is_active', true);
        $costCenter->update($data);

        AuditLog::log('UPDATE_COST_CENTER', "কস্ট সেন্টার '{$costCenter->name_bn}' আপডেট করা হয়েছে।");

        return back()->with('success', 'কস্ট সেন্টার সফলভাবে আপডেট করা হয়েছে।');
    }

    public function assignUsers(Request $request, CostCenter $costCenter): RedirectResponse
    {
        $request->validate(['user_ids' => ['nullable', 'array'], 'user_ids.*' => ['exists:users,id']]);
        $costCenter->users()->sync($request->input('user_ids', []));

        AuditLog::log('ASSIGN_COST_CENTER_USERS', "কস্ট সেন্টার '{$costCenter->code}' এ ব্যবহারকারী অ্যাসাইন করা হয়েছে।");

        return back()->with('success', 'ব্যবহারকারী সফলভাবে অ্যাসাইন করা হয়েছে।');
    }

    public function export(): StreamedResponse
    {
        $fileName = 'BPDB_Cost_Centers_' . date('Ymd_His') . '.csv';

        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");

            fputcsv($handle, ['কোড (Code)', 'নাম (Name EN)', 'বাংলা নাম (Name BN)', 'অবস্থান (Location)', 'জোন/বিভাগ (Zone)', 'যোগাযোগের কর্মকর্তা', 'ফোন নম্বর']);

            foreach (CostCenter::orderBy('code')->cursor() as $cc) {
                fputcsv($handle, [
                    $cc->code,
                    $cc->name,
                    $cc->name_bn,
                    $cc->location,
                    $cc->division_or_zone,
                    $cc->contact_person,
                    $cc->phone,
                ]);
            }

            fclose($handle);
        }, $fileName, ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function downloadTemplate(): StreamedResponse
    {
        return response()->streamDownload(function () {
            $handle = fopen('php://output', 'w');
            fputs($handle, "\xEF\xBB\xBF");
            fputcsv($handle, ['code', 'name', 'name_bn', 'location', 'division_or_zone', 'contact_person', 'phone']);
            fputcsv($handle, ['CC-201', 'Electric Supply Division, Barishal', 'বিদ্যুৎ সরবরাহ বিভাগ, বরিশাল', 'Band Road, Barishal', 'Barishal Zone', 'Executive Engineer', '0431-64001']);
            fclose($handle);
        }, 'Cost_Center_Import_Template.csv', ['Content-Type' => 'text/csv; charset=UTF-8']);
    }

    public function import(Request $request): RedirectResponse
    {
        $request->validate([
            'file' => ['required', 'file', 'mimes:csv,txt,xlsx,xls'],
        ]);

        $file = $request->file('file');
        $path = $file->getRealPath();

        $imported = 0;
        $handle = fopen($path, 'r');
        if ($handle !== false) {
            $header = fgetcsv($handle); // skip header row
            while (($row = fgetcsv($handle, 2000, ',')) !== false) {
                if (empty($row[0])) continue;
                $code = trim($row[0]);
                $name = trim($row[1] ?? $code);
                $nameBn = trim($row[2] ?? $name);
                $location = trim($row[3] ?? '');
                $zone = trim($row[4] ?? '');
                $contact = trim($row[5] ?? '');
                $phone = trim($row[6] ?? '');

                CostCenter::updateOrCreate(
                    ['code' => $code],
                    [
                        'name'             => $name,
                        'name_bn'          => $nameBn,
                        'location'         => $location,
                        'division_or_zone' => $zone,
                        'contact_person'   => $contact,
                        'phone'            => $phone,
                        'is_active'        => true,
                    ]
                );
                $imported++;
            }
            fclose($handle);
        }

        AuditLog::log('IMPORT_COST_CENTERS', "এক্সেল/সিএসভি ফাইল হতে মোট {$imported} টি কস্ট সেন্টার ইম্পোর্ট/আপডেট করা হয়েছে।");

        return back()->with('success', "মোট {$imported} টি কস্ট সেন্টার সফলভাবে ইম্পোর্ট/আপডেট করা হয়েছে।");
    }

    public function destroy(CostCenter $costCenter): RedirectResponse
    {
        if ($costCenter->vouchers()->count() > 0) {
            return back()->with('error', 'এই কস্ট সেন্টারের অধীনে ভাউচার লেনদেন থাকায় এটি মুছে ফেলা সম্ভব নয়।');
        }

        $costCenter->users()->detach();
        $costCenter->delete();

        AuditLog::log('DELETE_COST_CENTER', "কস্ট সেন্টার '{$costCenter->name_bn}' মুছে ফেলা হয়েছে।");

        return back()->with('success', 'কস্ট সেন্টার মুছে ফেলা হয়েছে।');
    }
}
