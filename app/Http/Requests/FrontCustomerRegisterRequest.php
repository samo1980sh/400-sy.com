<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;

class FrontCustomerRegisterRequest extends FormRequest
{
    protected $errorBag = 'customerRegister';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mobile = $this->normalizeMobileInput((string) $this->input('mobile', ''));
        $secondaryMobile = $this->normalizeMobileInput((string) $this->input('secondary_mobile', ''));

        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'mobile' => $mobile,
            'secondary_mobile' => $secondaryMobile !== '' ? $secondaryMobile : null,
            'email' => filled($this->input('email'))
                ? strtolower(trim((string) $this->input('email')))
                : null,
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
        return [
            'name' => ['required', 'string', 'min:5', 'max:255', 'regex:/^\S+\s+\S+\s+\S+/u'],
            'birth_date' => ['required', 'date', 'before_or_equal:today'],
            'nationality' => ['required', 'string', 'max:255'],
            'mobile' => ['required', 'string', 'size:9', 'regex:/^9[0-9]{8}$/', Rule::unique('customers', 'mobile')],
            'gender' => ['required', 'string', Rule::in(['male', 'female'])],
            'city' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
            'job_title' => ['nullable', 'string', 'max:255'],
            'secondary_mobile' => ['nullable', 'string', 'size:9', 'regex:/^9[0-9]{8}$/'],
            'marital_status' => ['nullable', 'string', Rule::in(['single', 'married', 'divorced', 'widowed'])],
            'email' => ['nullable', 'email:rfc', 'max:255', Rule::unique('customers', 'email')],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('front.auth.full_name'),
            'birth_date' => __('front.auth.birth_date'),
            'nationality' => __('front.auth.nationality'),
            'mobile' => __('front.auth.mobile_number'),
            'gender' => __('front.auth.gender'),
            'city' => __('front.auth.city'),
            'area' => __('front.auth.area'),
            'job_title' => __('front.auth.job_title'),
            'secondary_mobile' => __('front.auth.secondary_mobile'),
            'marital_status' => __('front.auth.marital_status'),
            'email' => __('front.auth.email'),
            'password' => __('front.auth.password_plain'),
        ];
    }

    public function messages(): array
    {
        return [
            'name.regex' => __('front.auth.full_name_three_parts'),
            'mobile.regex' => __('front.auth.mobile_invalid_syria'),
            'mobile.size' => __('front.auth.mobile_invalid_syria'),
            'secondary_mobile.regex' => __('front.auth.mobile_invalid_syria'),
            'secondary_mobile.size' => __('front.auth.mobile_invalid_syria'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash('auth_modal', 'register');
        parent::failedValidation($validator);
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
