<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class UpdateFrontCustomerProfileRequest extends FormRequest
{
    public function authorize(): bool
    {
        return auth('customer')->check();
    }

    protected function prepareForValidation(): void
    {
        $secondaryMobile = strtr((string) $this->input('secondary_mobile', ''), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'email' => filled($this->input('email')) ? strtolower(trim((string) $this->input('email'))) : null,
            'secondary_mobile' => filled($secondaryMobile)
                ? (preg_replace('/[\s\-()]+/', '', trim($secondaryMobile)) ?: null)
                : null,
            'city' => filled($this->input('city')) ? trim((string) $this->input('city')) : null,
            'area' => filled($this->input('area')) ? trim((string) $this->input('area')) : null,
        ]);
    }

    public function rules(): array
    {
        $customerId = auth('customer')->id();

        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('customers', 'email')->ignore($customerId)],
            'secondary_mobile' => ['nullable', 'string', 'min:7', 'max:30', 'regex:/^\+?[0-9]+$/'],
            'birth_date' => ['nullable', 'date', 'before_or_equal:today'],
            'city' => ['nullable', 'string', 'max:255'],
            'area' => ['nullable', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('front.account.name'),
            'email' => __('front.account.email'),
            'secondary_mobile' => __('front.account.secondary_mobile'),
            'birth_date' => __('front.account.birth_date'),
            'city' => __('front.account.city'),
            'area' => __('front.account.area'),
        ];
    }
}
