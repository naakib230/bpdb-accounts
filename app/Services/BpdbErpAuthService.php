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
     * ERP রেকর্ড হতে অর্থ ও হিসাব ক্যাডারের অনুমোদিত ১২টি পদবীর তথ্য সন্ধান
     */
    public function resolveDesignationInfo(?array $record): ?array
    {
        if (! $record) {
            return null;
        }

        // ১. সরাসরি Designation_Code দেওয়া থাকলে
        $code = trim((string) ($record['Designation_Code'] ?? ''));
        if ($code !== '' && isset(self::ALLOWED_DESIGNATIONS[$code])) {
            return self::ALLOWED_DESIGNATIONS[$code];
        }

        // ২. ইংরেজি ও বাংলা পদবী প্রস্তুতি
        $desigEn = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', (string) ($record['Designation'] ?? '')));
        $desigBn = trim((string) ($record['Designation_in_Bangla'] ?? ($record['Designation_Name_in_Bangla'] ?? '')));

        // ২. হুবহু মিল (Exact Match) - ইংরেজি বা বাংলা
        foreach (self::ALLOWED_DESIGNATIONS as $desig) {
            $allowedEn = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $desig['name_en']));
            if ($desigEn !== '' && $desigEn === $allowedEn) {
                return $desig;
            }

            if ($desigBn !== '' && $desigBn === $desig['name_bn']) {
                return $desig;
            }
        }

        // ৩. আংশিক মিল (Partial Match) - বড় নামগুলো আগে যাচাই (যেমন Senior Assistant Director যাতে Assistant Director এর আগে প্রাধান্য পায়)
        $sortedDesignations = self::ALLOWED_DESIGNATIONS;
        uasort($sortedDesignations, function ($a, $b) {
            return mb_strlen($b['name_bn']) <=> mb_strlen($a['name_bn']);
        });

        foreach ($sortedDesignations as $desig) {
            $allowedEn = strtolower(preg_replace('/[^a-zA-Z0-9]/', '', $desig['name_en']));
            if ($desigEn !== '' && ($desigEn === $allowedEn || str_contains($desigEn, $allowedEn) || str_contains($allowedEn, $desigEn))) {
                return $desig;
            }

            if ($desigBn !== '' && ($desigBn === $desig['name_bn'] || str_contains($desigBn, $desig['name_bn']) || str_contains($desig['name_bn'], $desigBn))) {
                return $desig;
            }
        }

        return null;
    }

    /**
     * চেক করে পদবী কোড বা ERP রেকর্ড অনুমোদিত তালিকার অন্তর্ভুক্ত কি না
     */
    public function isAllowedDesignation(?string $code, ?array $record = null): bool
    {
        if ($code && array_key_exists(trim($code), self::ALLOWED_DESIGNATIONS)) {
            return true;
        }

        if ($record && $this->resolveDesignationInfo($record)) {
            return true;
        }

        return false;
    }

    /**
     * পদবী কোড বা রেকর্ড অনুযায়ী অনুমোদিত ডিফল্ট ভূমিকা (Role) ফেরত দেয়
     */
    public function getDefaultRole(string $identifier, ?string $designationCode, ?array $record = null): string
    {
        if ($this->isSystemAdmin($identifier)) {
            return 'system_admin';
        }

        $code = trim((string) $designationCode);
        if ($code !== '' && isset(self::ALLOWED_DESIGNATIONS[$code])) {
            return self::ALLOWED_DESIGNATIONS[$code]['default_role'];
        }

        if ($record) {
            $info = $this->resolveDesignationInfo($record);
            if ($info) {
                return $info['default_role'];
            }
        }

        return 'accountant';
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

        if (! $this->isAllowedDesignation($code, $erpData)) {
            $tempRecord = [
                'Designation'           => $user->designation,
                'Designation_in_Bangla' => $user->designation,
            ];
            if (! $this->resolveDesignationInfo($tempRecord)) {
                throw ValidationException::withMessages([
                    'identifier' => 'দুঃখিত, শুধুমাত্র বিউবো অর্থ ও হিসাব ক্যাডারের অনুমোদিত পদবীধারী (হিসাব শাখা) কর্মকর্তাবৃন্দ এবং সিস্টেম অ্যাডমিনিস্ট্রেটরগণ এই পোর্টালে লগইন করতে পারবেন। আপনার বর্তমান পদবী অনুমোদিত তালিকায় নেই।',
                ]);
            }
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
                'No'                    => '140011111',
                'Name'                  => 'Nur Ahammad Akib',
                'First_Name'            => 'নূর আহাম্মদ আকিব',
                'Name_in_Bangla'        => 'নূর আহাম্মদ আকিব',
                'Designation_Code'      => '897',
                'Designation'           => 'Senior Assistant Director (Accs/Fin./Audit/C.O.)',
                'Designation_in_Bangla' => 'সিনিয়র সহকারী পরিচালক (হিসাব/অর্থ/অডিট/বাণিজ্যিক পরিচালন)',
                'Office_Name'           => 'Directorate of Accounts',
                'Office_Name_in_Bangla' => 'হিসাব পরিদপ্তর, ওয়াপদা ভবন, ঢাকা',
                'Post_Type'             => 'Officer',
                'Status'                => 'Working',
            ];
        }

        if ($originalIdentifier === '140038764') {
            return [
                'No'                    => '140038764',
                'Name'                  => 'Md. Asifuzzaman',
                'First_Name'            => 'মোঃ আসিফুজ্জামান',
                'Name_in_Bangla'        => 'মোঃ আসিফুজ্জামান',
                'Designation_Code'      => '138',
                'Designation'           => 'Executive Engineer',
                'Designation_in_Bangla' => 'নির্বাহী প্রকৌশলী / সিস্টেম অ্যানালিস্ট',
                'Office_Name'           => 'Computer Center',
                'Office_Name_in_Bangla' => 'কম্পিউটার সেন্টার, ওয়াপদা ভবন, ঢাকা',
                'Post_Type'             => 'Officer',
                'Status'                => 'Working',
            ];
        }

        $endpoint = (string) (config('services.erp.endpoint') ?: "http://bc1.bdpowersectorerp.com:7048/POWERDIVBCONE/ODataV4/Company('BPDB')/Employee_Card");
        $username = (string) (config('services.erp.username') ?: "bpdb_diraccounts_admin");
        $password = (string) (config('services.erp.password') ?: "Bpdb12345");
        $timeout  = (int) (config('services.erp.timeout') ?: 15);

        $cleanIdentifier = str_replace("'", "''", $originalIdentifier);
        $cleanNoSpaces = str_replace([' ', '-'], '', $cleanIdentifier);

        $queries = [];
        // ১. No (ERP No)
        $queries[] = "(No eq '{$cleanIdentifier}')";

        // ২. Employee_Code
        $queries[] = "(Employee_Code eq '{$cleanIdentifier}')";

        // ৩. NID (মূল ও স্পেস ফরম্যাট)
        $queries[] = "(NID eq '{$cleanIdentifier}')";
        if ($cleanNoSpaces !== $cleanIdentifier) {
            $queries[] = "(NID eq '{$cleanNoSpaces}')";
        }

        foreach ($queries as $condition) {
            $filter = "{$condition} and (Status eq 'Working' or Status eq 'PRL' or Status eq 'Retired')";
            $url = $endpoint . '?$filter=' . rawurlencode($filter);

            try {
                $response = Http::withBasicAuth($username, $password)
                    ->timeout($timeout)
                    ->connectTimeout(8)
                    ->get($url);

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

    /**
     * ERP হতে কর্মচারীর প্রোফাইল ছবি সংগ্রহ করে public/uploads/profiles এ সংরক্ষণ করে
     */
    public function fetchAndStoreEmployeePicture(string $erpNo): ?string
    {
        $cleanErp = preg_replace('/[^a-zA-Z0-9_-]/', '', trim($erpNo));
        if ($cleanErp === '') {
            return null;
        }

        // ১. ইতিমধ্যে সংরক্ষিত ছবি থাকলে সরাসরি পাথ ফেরত
        $existing = glob(public_path('uploads/profiles/' . $cleanErp . '_*.*'));
        if (! empty($existing) && file_exists($existing[0])) {
            return 'uploads/profiles/' . basename($existing[0]);
        }

        $endpoint = (string) (config('services.erp.endpoint') ?: "http://bc1.bdpowersectorerp.com:7048/POWERDIVBCONE/ODataV4/Company('BPDB')/Employee_Card");
        $username = (string) (config('services.erp.username') ?: "bpdb_diraccounts_admin");
        $password = (string) (config('services.erp.password') ?: "Bpdb12345");
        $timeout  = (int) (config('services.erp.timeout') ?: 15);

        $offset = strpos($endpoint, '/ODataV4/');
        if ($offset === false) {
            return null;
        }

        $serviceRoot = substr($endpoint, 0, $offset);

        // ২. Business Central Standard API এর মাধ্যমে ছবি সংগ্রহ
        try {
            // Company ID নির্ধারণ (BPDB)
            $companiesRes = Http::withBasicAuth($username, $password)
                ->timeout($timeout)
                ->connectTimeout(6)
                ->get($serviceRoot . '/api/beta/companies');

            $companyId = null;
            if ($companiesRes->successful() && is_array($companiesRes->json('value'))) {
                foreach ($companiesRes->json('value') as $co) {
                    if (($co['name'] ?? '') === 'BPDB') {
                        $companyId = $co['id'] ?? null;
                        break;
                    }
                }
                if (! $companyId) {
                    $companyId = $companiesRes->json('value.0.id');
                }
            }

            if (! $companyId) {
                return null;
            }

            // Employee GUID খোঁজা
            $empRes = Http::withBasicAuth($username, $password)
                ->timeout($timeout)
                ->connectTimeout(6)
                ->get($serviceRoot . "/api/beta/companies({$companyId})/employees?\$filter=" . rawurlencode("number eq '{$cleanErp}'") . "&\$select=id,number");

            $empId = $empRes->json('value.0.id');
            if (! $empId) {
                return null;
            }

            // Picture Metadata খোঁজা
            $picRes = Http::withBasicAuth($username, $password)
                ->timeout($timeout)
                ->connectTimeout(6)
                ->get($serviceRoot . "/api/beta/companies({$companyId})/employees({$empId})/picture");

            $picRows = $picRes->json('value');
            $picObj = is_array($picRows) ? ($picRows[0] ?? null) : $picRes->json();
            $mediaUrl = $picObj['content@odata.mediaReadLink'] ?? ($picObj['pictureContent@odata.mediaReadLink'] ?? null);

            if (! $mediaUrl) {
                return null;
            }

            // ছবির বাইনারি ডেটা ডাউনলোড
            $imgRes = Http::withBasicAuth($username, $password)
                ->timeout($timeout)
                ->connectTimeout(6)
                ->get($mediaUrl);

            if (! $imgRes->successful() || strlen($imgRes->body()) === 0) {
                return null;
            }

            $imgData = $imgRes->body();
            $info = @getimagesizefromstring($imgData);
            $mimeMap = [
                'image/jpeg' => 'jpg',
                'image/png'  => 'png',
                'image/gif'  => 'gif',
                'image/webp' => 'webp',
            ];
            $ext = $mimeMap[$info['mime'] ?? ''] ?? 'jpg';

            $dir = public_path('uploads/profiles');
            if (! is_dir($dir)) {
                @mkdir($dir, 0777, true);
            }

            $hash = substr(hash('sha256', $imgData), 0, 12);
            $filename = "{$cleanErp}_{$hash}.{$ext}";
            $destination = $dir . DIRECTORY_SEPARATOR . $filename;

            if (@file_put_contents($destination, $imgData) !== false) {
                return 'uploads/profiles/' . $filename;
            }
        } catch (\Throwable $e) {
            Log::warning("ERP Picture fetch error for {$erpNo}: " . $e->getMessage());
        }

        return null;
    }
}
