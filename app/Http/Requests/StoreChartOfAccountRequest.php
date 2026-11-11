<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreChartOfAccountRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        $coa = $this->route('chart_of_account');
        $coaId = is_object($coa) ? $coa->id : $coa;

        return [
            'account_code'    => [
                'required',
                'string',
                'max:50',
                $coaId
                    ? Rule::unique('chart_of_accounts', 'account_code')->ignore($coaId)
                    : Rule::unique('chart_of_accounts', 'account_code'),
            ],
            'account_name'    => ['required', 'string', 'max:255'],
            'account_name_bn' => ['nullable', 'string', 'max:255'],
            'account_type'    => ['required', 'in:asset,liability,equity,revenue,expense'],
            'income_balance'  => ['nullable', 'string', 'max:50'],
            'control_head'    => ['nullable', 'string', 'max:191'],
            'parent_id'       => ['nullable', 'exists:chart_of_accounts,id'],
            'normal_balance'  => ['required', 'in:debit,credit'],
            'opening_balance' => ['nullable', 'numeric'],
            'is_leaf'         => ['nullable', 'boolean'],
            'direct_posting'  => ['nullable', 'boolean'],
            'is_active'       => ['nullable', 'boolean'],
            'description'     => ['nullable', 'string'],
        ];
    }

    public function messages(): array
    {
        return [
            'account_code.required' => 'হিসাব কোড আবশ্যক।',
            'account_code.unique'   => 'এই হিসাব কোডটি ইতোমধ্যে বিদ্যমান।',
            'account_name.required' => 'হিসাব খাতের ইংরেজি নাম আবশ্যক।',
            'account_type.required' => 'হিসাবের ধরন নির্বাচন আবশ্যক।',
            'normal_balance.required' => 'স্বাভাবিক ব্যালেন্স (ডেবিট/ক্রেডিট) নির্বাচন আবশ্যক।',
        ];
    }
}
