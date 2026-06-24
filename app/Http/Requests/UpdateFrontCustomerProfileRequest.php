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
        $secondaryMobile = $this->normalizeMobileInput((string) $this->input('secondary_mobile', ''));

        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'email' => filled($this->input('email')) ? strtolower(trim((string) $this->input('email'))) : null,
            'secondary_mobile' => $secondaryMobile !== '' ? $secondaryMobile : null,
            'nationality' => filled($this->input('nationality')) ? trim((string) $this->input('nationality')) : null,
            'gender' => filled($this->input('gender')) ? trim((string) $this->input('gender')) : null,
            'city' => filled($this->input('city')) ? trim((string) $this->input('city')) : null,
            'area' => filled($this->input('area')) ? trim((string) $this->input('area')) : null,
            'job_title' => filled($this->input('job_title')) ? trim((string) $this->input('job_title')) : null,
            'marital_status' => filled($this->input('marital_status')) ? trim((string) $this->input('marital_status')) : null,
        ]);
    }

    public function rules(): array
    {
        $customerId = auth('customer')->id();

        return [
            'name' => ['required', 'string', 'min:5', 'max:255', 'regex:/^\S+\s+\S+\s+\S+/u'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'nationality' => ['required', 'string', 'max:255'],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'city' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('customers', 'email')->ignore($customerId)],
            'secondary_mobile' => ['nullable', 'string', 'size:9', 'regex:/^9[0-9]{8}$/'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'marital_status' => ['nullable', 'string', Rule::in(['single', 'married', 'divorced', 'widowed'])],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('front.account.name'),
            'birth_date' => __('front.account.birth_date'),
            'nationality' => __('front.account.nationality'),
            'gender' => __('front.account.gender'),
            'city' => __('front.account.city'),
            'area' => __('front.account.area'),
            'email' => __('front.account.email'),
            'secondary_mobile' => __('front.account.secondary_mobile'),
            'job_title' => __('front.account.job_title'),
            'marital_status' => __('front.account.marital_status'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => __('front.auth.full_name_three_parts'),
            'secondary_mobile.regex' => __('front.auth.mobile_invalid_syria'),
            'secondary_mobile.size' => __('front.auth.mobile_invalid_syria'),
        ];
    }

    protected function normalizeMobileInput(string $mobile): string
    {
        $mobile = strtr($mobile, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        $mobile = preg_replace('/[\s\-()]+/', '', trim($mobile)) ?: '';

        return str_starts_with($mobile, '0') && strlen($mobile) === 10
            ? substr($mobile, 1)
            : $mobile;
    }
}