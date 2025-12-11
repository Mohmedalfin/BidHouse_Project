<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class BidStoreRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    public function rules(): array
    {
        return [
            'amount' => 'required|integer|min:1',
        ];
    }
    
    public function messages(): array
    {
        return [
            'amount.required' => 'Nominal bid wajib diisi.',
            'amount.integer'  => 'Nominal bid harus berupa angka.',
            'amount.min'      => 'Nominal bid tidak boleh kurang dari 1 rupiah.',
        ];
    }
}