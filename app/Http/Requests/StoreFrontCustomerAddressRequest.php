<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class StoreFrontCustomerAddressRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    protected function prepareForValidation(): void
    {
        $mobile = strtr((string) $this->input('mobile', ''), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $this->merge([
            'label' => filled($this->input('label')) ? trim((string) $this->input('label')) : null,
            'contact_name' => trim((string) $this->input('contact_name', '')),
            'mobile' => preg_replace('/[\s\-()]+/', '', trim($mobile)) ?: '',
            'city' => trim((string) $this->input('city', '')),
            'area' => trim((string) $this->input('area', '')),
            'address_line' => trim((string) $this->input('address_line', '')),
            'notes' => filled($this->input('notes')) ? trim((string) $this->input('notes')) : null,
            'is_default' => $this->boolean('is_default'),
        ]);
    }

    public function rules(): array
    {
        return [
            'label' => ['nullable', 'string', 'max:255'],
            'contact_name' => ['required', 'string', 'min:2', 'max:255'],
            'mobile' => ['required', 'string', 'min:7', 'max:30', 'regex:/^\+?[0-9]+$/'],
            'city' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
            'address_line' => ['required', 'string', 'min:5', 'max:1000'],
            'address_type' => ['required', Rule::in(['home', 'work', 'other'])],
            'is_default' => ['boolean'],
            'notes' => ['nullable', 'string', 'max:1000'],
        ];
    }

    public function attributes(): array
    {
        return [
            'label' => __('front.account.address_label'),
            'contact_name' => __('front.account.contact_name'),
            'mobile' => __('front.account.mobile'),
            'city' => __('front.account.city'),
            'area' => __('front.account.area'),
            'address_line' => __('front.account.address_line'),
            'address_type' => __('front.account.address_type'),
            'is_default' => __('front.account.default_address'),
            'notes' => __('front.account.notes'),
        ];
    }
}
