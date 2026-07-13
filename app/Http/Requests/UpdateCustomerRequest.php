<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\ValidationRule;
use Illuminate\Foundation\Http\FormRequest;

class UpdateCustomerRequest extends FormRequest
{
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
        $customerId = $this->route('customer') ?? $this->route('id');

        return [
            'router_id' => ['required', 'exists:routers,id'],
            'package_id' => ['required', 'exists:packages,id'],
            'name' => ['required', 'string', 'max:255'],
            'phone' => ['required', 'string', 'max:50'],
            'whatsapp' => ['required', 'string', 'max:50'],
            'email' => ['nullable', 'email', 'max:255'],
            'ktp_number' => ['nullable', 'string', 'max:50'],
            'ktp_photo' => ['nullable', 'image', 'max:2048'],
            'photo' => ['nullable', 'image', 'max:2048'],
            'address' => ['required', 'string'],
            'lat' => ['nullable', 'numeric', 'between:-90,90'],
            'lng' => ['nullable', 'numeric', 'between:-180,180'],
            'pppoe_username' => ['required', 'string', 'max:255', 'unique:customers,pppoe_username,' . $customerId],
            'pppoe_password' => ['nullable', 'string', 'max:255'],
        ];
    }
}
