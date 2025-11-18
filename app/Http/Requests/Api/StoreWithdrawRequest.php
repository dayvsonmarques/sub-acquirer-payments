<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreWithdrawRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'bank_code' => ['required', 'string', 'max:10'],
            'agency' => ['required', 'string', 'max:20'],
            'account' => ['required', 'string', 'max:20'],
            'account_type' => ['required', 'string', Rule::in(['checking', 'savings'])],
            'account_holder_name' => ['required', 'string', 'max:255'],
            'account_holder_document' => ['required', 'string', 'max:20'],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
