<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class UpdateVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'date'                          => ['sometimes', 'date'],
            'cost_center_id'                => ['sometimes', 'exists:cost_centers,id'],
            'bank_account_id'               => ['nullable', 'exists:bank_accounts,id'],
            'cheque_number'                 => ['nullable', 'string', 'max:50'],
            'cheque_date'                   => ['nullable', 'date'],
            'payee_payer_name'              => ['nullable', 'string', 'max:191'],
            'narration'                     => ['sometimes', 'string'],
            'rejection_reason'              => ['nullable', 'string', 'max:500'],
            'entries'                       => ['nullable', 'array', 'min:2'],
            'entries.*.chart_of_account_id' => ['required_with:entries', 'exists:chart_of_accounts,id'],
            'entries.*.debit_amount'        => ['nullable', 'numeric', 'min:0'],
            'entries.*.credit_amount'       => ['nullable', 'numeric', 'min:0'],
            'entries.*.line_narration'      => ['nullable', 'string', 'max:255'],
        ];
    }
}
