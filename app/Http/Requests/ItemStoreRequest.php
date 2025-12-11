<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class ItemStoreRequest extends FormRequest
{
    /**
     * Determine if the user is authorized to make this request.
     * Kita set true dulu, logic admin-nya kita taruh di Controller biar eksplisit.
     */
    public function authorize(): bool
    {
        return true; 
    }

    /**
     * Get the validation rules that apply to the request.
     */
    public function rules(): array
    {
        return [
            'name'          => 'required|string|max:255',
            'description'   => 'required|string',
            'initial_price' => 'required|integer|min:1000', 
            'image'         => 'nullable|string', 
            'end_at'        => 'required|date|after:now', 
        ];
    }
}