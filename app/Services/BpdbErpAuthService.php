<?php

namespace App\Services;

use App\Models\User;
use Illuminate\Http\Client\ConnectionException;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Validation\ValidationException;

class BpdbErpAuthService
{
    /**
     * অর্থ ও হিসাব ক্যাডারের অনুমোদিত ১২টি পদবীর তালিকা (BPDB Finance Cadre)
     */
    public const ALLOWED_DESIGNATIONS = [
        '102' => [
            'code'         => '102',
            'name_en'      => 'Controller',
            'name_bn'      => 'নিয়ন্ত্রক, অর্থ ও হিসাব',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'director_accounts',
        ],
        '149' => [
            'code'         => '149',
            'name_en'      => 'Assistant Director (Accounts/Finance/Audit/CO)',
            'name_bn'      => 'সহকারী পরিচালক (হিসাব/অর্থ/অডিট/বাণিজ্যিক পরিচালন)',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'accounts_officer',
        ],
        '181' => [
            'code'         => '181',
            'name_en'      => 'Accountant',
            'name_bn'      => 'হিসাবরক্ষক',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'accountant',
        ],
        '234' => [
            'code'         => '234',
            'name_en'      => 'Senior Accounts Assistant',
            'name_bn'      => 'উচ্চমান হিসাব সহকারী',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'accountant',
        ],
        '237' => [
            'code'         => '237',
            'name_en'      => 'Assistant Accountant',
            'name_bn'      => 'সহকারী হিসাবরক্ষক',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'accountant',
        ],
        '355' => [
            'code'         => '355',
            'name_en'      => 'Junior Accounts Assistant',
            'name_bn'      => 'নিম্নমান হিসাব সহকারী',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'accountant',
        ],
        '786' => [
            'code'         => '786',
            'name_en'      => 'Senior Account Assistant Cum Cashier',
            'name_bn'      => 'এস.এ.এ',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'accountant',
        ],
        '895' => [
            'code'         => '895',
            'name_en'      => 'Additional Director (Accounts/Finance/Audit/CO)',
            'name_bn'      => 'অতিরিক্ত পরিচালক (হিসাব/অর্থ/অডিট/বাণিজ্যিক পরিচালন)',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'director_accounts',
        ],
        '896' => [
            'code'         => '896',
            'name_en'      => 'Deputy Director (Accounts/Finance/Audit/CO)',
            'name_bn'      => 'উপপরিচালক (হিসাব/অর্থ/অডিট/বাণিজ্যিক পরিচালন)',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'accounts_officer',
        ],
        '897' => [
            'code'         => '897',
            'name_en'      => 'Senior Assistant Director (Accs/Fin./Audit/C.O.)',
            'name_bn'      => 'সিনিয়র সহকারী পরিচালক (হিসাব/অর্থ/অডিট/বাণিজ্যিক পরিচালন)',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'accounts_officer',
        ],
        '985' => [
            'code'         => '985',
            'name_en'      => 'Director (Accounts/Finance/Audit/CO)',
            'name_bn'      => 'পরিচালক (হিসাব/অর্থ/অডিট/বাণিজ্যিক পরিচালন)',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'director_accounts',
        ],
        '987' => [
            'code'         => '987',
            'name_en'      => 'Deputy Secretary (Finance)',
            'name_bn'      => 'উপ সচিব (অর্থ)',
            'type'         => 'Non-Technical',
            'cadre'        => 'Finance Cadre',
            'default_role' => 'director_accounts',
        ],
    ];

    /**
     * অনুমোদিত সিস্টেম অ্যাডমিনিস্ট্রেটরদের ERP তালিকা
     */
    public const SYSTEM_ADMIN_ERPS = [
        '140011111',
        '140038764',
    ];

    /**
     * চেক করে ইনপুট ERP সিস্টেম অ্যাডমিনের অন্তর্ভুক্ত কি না
     */
    public function isSystemAdmin(string $identifier): bool
    {
        $clean = trim($identifier);
        return in_array($clean, self::SYSTEM_ADMIN_ERPS, true);
    }

    /**
     * চেক করে পদবী কোড অনুমোদিত তালিকার অন্তর্ভুক্ত কি না
     */
    public function isAllowedDesignation(?string $code): bool
    {
        if (! $code) return false;
        return array_key_exists(trim($code), self::ALLOWED_DESIGNATIONS);
    }

    /**
     * পদবী কোড অনুযায়ী অনুমোদিত ডিফল্ট ভূমিকা (Role) ফেরত দেয়
     */
    public function getDefaultRole(string $identifier, ?string $designationCode): string
    {
        if ($this->isSystemAdmin($identifier)) {
            return 'system_admin';
        }

        $code = trim((string) $designationCode);
        return self::ALLOWED_DESIGNATIONS[$code]['default_role'] ?? 'accountant';
    }

    /**
     * ইউজারের পদবী ও এক্সেস যাচাই (Authorization)
     */
    public function verifyEligibility(User $user, ?array $erpData = null): void
    {
        // ১. সিস্টেম অ্যাডমিন হলে সরাসরি অনুমোদিত
        if ($this->isSystemAdmin($user->username) || ($user->erp_no && $this->isSystemAdmin($user->erp_no))) {
            return;
        }

        // ২. ইউজারের সংরক্ষিত পদবী কোড চেক
        $code = (string) ($user->designation_code ?: ($erpData['Designation_Code'] ?? ''));

        if (! $this->isAllowedDesignation($code)) {
            throw ValidationException::withMessages([
                'identifier' => 'দুঃখিত, শুধুমাত্র বিউবো অর্থ ও হিসাব ক্যাডারের অনুমোদিত পদবীধারী (হিসাব শাখা) কর্মকর্তাবৃন্দ এবং সিস্টেম অ্যাডমিনিস্ট্রেটরগণ এই পোর্টালে লগইন করতে পারবেন। আপনার বর্তমান পদবী অনুমোদিত তালিকায় নেই।',
            ]);
        }
    }

    /**
     * BPDB কেন্দ্রীয় ERP (Dynamics 365 OData) থেকে কর্মকর্তা/কর্মচারীর তথ্য সন্ধান
     */
    public function fetchEmployeeFromErp(string $identifier): ?array
    {
        $originalIdentifier = trim($identifier);
        if ($originalIdentifier === '') {
            return null;
        }

        // হার্ডকোডেড সিস্টেম অ্যাডমিন ফলব্যাক ডেটা
        if ($originalIdentifier === '140011111') {
            return [
                'No'               => '140011111',
                'Name'             => 'Nur Ahammad Akib',
                'First_Name'       => 'নূর আহাম্মদ আকিব',
                'Designation_Code' => '897',
                'Designation'      => 'সিনিয়র সহকারী পরিচালক (হিসাব/অর্থ/অডিট/বাণিজ্যিক পরিচালন)',
                'Office_Name'      => 'হিসাব পরিদপ্তর, ওয়াপদা ভবন, ঢাকা',
                'Post_Type'        => 'Officer',
                'Status'           => 'Working',
            ];
        }

        if ($originalIdentifier === '140038764') {
            return [
                'No'               => '140038764',
                'Name'             => 'Md. Asifuzzaman',
                'First_Name'       => 'মোঃ আসিফুজ্জামান',
                'Designation_Code' => '138',
                'Designation'      => 'নির্বাহী প্রকৌশলী / সিস্টেম অ্যানালিস্ট',
                'Office_Name'      => 'কম্পিউটার সেন্টার, ওয়াপদা ভবন, ঢাকা',
                'Post_Type'        => 'Officer',
                'Status'           => 'Working',
            ];
        }

        $endpoint = (string) config('services.erp.endpoint', '');
        if ($endpoint === '') {
            return null;
        }

        $cleanIdentifier = str_replace("'", "''", $originalIdentifier);

        foreach (['No', 'NID', 'Employee_Code'] as $field) {
            $filter = "({$field} eq '{$cleanIdentifier}') and (Status eq 'Working' or Status eq 'PRL' or Status eq 'Retired')";
            $url = $endpoint . '?$filter=' . rawurlencode($filter);

            try {
                $response = Http::withBasicAuth(
                    (string) config('services.erp.username', ''),
                    (string) config('services.erp.password', '')
                )->timeout((int) config('services.erp.timeout', 15))->get($url);

                if ($response->successful() && is_array($response->json('value'))) {
                    $rows = $response->json('value');
                    if (! empty($rows)) {
                        return $rows[0];
                    }
                }
            } catch (ConnectionException $e) {
                Log::warning('ERP connection error during employee lookup: ' . $e->getMessage());
            } catch (\Throwable $e) {
                Log::warning('ERP error: ' . $e->getMessage());
            }
        }

        return null;
    }
}
