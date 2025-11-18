<?php

namespace App\Http\Requests\Api;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StorePixRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => ['required', 'numeric', 'min:0.01'],
            'pix_key' => ['required', 'string', 'max:255'],
            'pix_key_type' => ['required', 'string', Rule::in(['cpf', 'email', 'phone', 'random'])],
            'description' => ['nullable', 'string', 'max:500'],
        ];
    }
}
