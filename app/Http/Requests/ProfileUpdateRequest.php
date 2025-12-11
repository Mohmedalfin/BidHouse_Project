<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ProfileUpdateRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     */
    public function authorize(): bool
    {
        return true;
    }

    /**
     * Get the validation rules that apply to the request.
     *
     * @return array<string, \Illuminate\Contracts\Validation\ValidationRule|array<mixed>|string>
     */
    public function rules(): array
    {
        return [
            // kalau dikirim, wajib diisi dan string
            'name' => ['sometimes', 'required', 'string', 'max:255'],

            // kalau dikirim, wajib format email, dan unik (kecuali email milik user sendiri)
            'email' => [
                'sometimes',
                'required',
                'email',
                'max:255',
                'unique:users,email,' . $this->user()->id,
            ],

            // kalau dikirim, wajib minimal 8 dan pakai password_confirmation
            'password' => ['sometimes', 'required', 'min:8', 'confirmed'],
        ];
    }
}
