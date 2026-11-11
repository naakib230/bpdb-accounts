<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreBudgetRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'fiscal_year_id'                    => ['required', 'exists:fiscal_years,id'],
            'cost_center_id'                    => ['required', 'exists:cost_centers,id'],
            'allocations'                       => ['required', 'array'],
            'allocations.*.chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
            'allocations.*.original_budget'     => ['nullable', 'numeric', 'min:0'],
            'allocations.*.revised_budget'      => ['nullable', 'numeric', 'min:0'],
            'allocations.*.notes'               => ['nullable', 'string', 'max:255'],
        ];
    }

    public function messages(): array
    {
        return [
            'fiscal_year_id.required' => 'অর্থবছর নির্বাচন আবশ্যক।',
            'cost_center_id.required' => 'কস্ট সেন্টার নির্বাচন আবশ্যক।',
            'allocations.required'    => 'বাজেট বরাদ্দের তথ্য প্রদান আবশ্যক।',
        ];
    }
}
