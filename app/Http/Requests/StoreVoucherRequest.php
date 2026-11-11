<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class StoreVoucherRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        // Journal Voucher Rules
        if ($this->has('entries') || $this->routeIs('vouchers.store_journal')) {
            return [
                'date'                          => ['required', 'date'],
                'cost_center_id'                => ['required', 'exists:cost_centers,id'],
                'narration'                     => ['required', 'string'],
                'submit_action'                 => ['required', 'in:save_draft,submit_for_approval'],
                'entries'                       => ['required', 'array', 'min:2'],
                'entries.*.chart_of_account_id' => ['required', 'exists:chart_of_accounts,id'],
                'entries.*.debit_amount'        => ['nullable', 'numeric', 'min:0'],
                'entries.*.credit_amount'       => ['nullable', 'numeric', 'min:0'],
                'entries.*.line_narration'      => ['nullable', 'string', 'max:255'],
            ];
        }

        // Cash / Bank Voucher Rules
        return [
            'date'                  => ['required', 'date'],
            'cash_type'             => ['required', 'in:cash_payment,cash_receipt,bank_payment,bank_receipt'],
            'cost_center_id'        => ['required', 'exists:cost_centers,id'],
            'bank_account_id'       => ['nullable', 'required_if:cash_type,bank_payment,bank_receipt', 'exists:bank_accounts,id'],
            'cheque_number'         => ['nullable', 'string', 'max:50'],
            'cheque_date'           => ['nullable', 'date'],
            'payee_payer_name'      => ['nullable', 'string', 'max:191'],
            'contra_account_id'     => ['required', 'exists:chart_of_accounts,id'],
            'amount'                => ['required', 'numeric', 'min:0.01'],
            'narration'             => ['required', 'string'],
            'submit_action'         => ['required', 'in:save_draft,submit_for_approval'],
        ];
    }

    public function messages(): array
    {
        return [
            'date.required'                  => 'ভাউচারের তারিখ প্রদান আবশ্যক।',
            'cost_center_id.required'        => 'কস্ট সেন্টার নির্বাচন আবশ্যক।',
            'bank_account_id.required_if'    => 'ব্যাংক পেমেন্ট বা প্রাপ্তির ক্ষেত্রে ব্যাংক অ্যাকাউন্ট নির্বাচন আবশ্যক।',
            'contra_account_id.required'     => 'বিপরীত হিসাব খাত (Contra Account) নির্বাচন আবশ্যক।',
            'amount.required'                => 'টাকার পরিমাণ আবশ্যক।',
            'amount.min'                     => 'টাকার পরিমাণ অবশ্যই ০.০১ এর চেয়ে বেশি হতে হবে।',
            'entries.required'               => 'জার্নাল ভাউচারে এন্ট্রি প্রদান আবশ্যক।',
            'entries.min'                    => 'জার্নাল ভাউচারে কমপক্ষে দুইটি এন্ট্রি থাকতে হবে।',
        ];
    }
}
