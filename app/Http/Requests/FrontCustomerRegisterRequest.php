<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class FrontCustomerRegisterRequest extends FormRequest
{
    protected $errorBag = 'customerRegister';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mobile = strtr((string) $this->input('mobile', ''), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $this->merge([
            'name' => trim((string) $this->input('name', '')),
            'mobile' => preg_replace('/[\s\-()]+/', '', trim($mobile)) ?: '',
            'email' => filled($this->input('email'))
                ? strtolower(trim((string) $this->input('email')))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:255'],
            'mobile' => ['required', 'string', 'min:7', 'max:30', 'regex:/^\+?[0-9]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('front.auth.full_name'),
            'mobile' => __('front.auth.mobile_number'),
            'email' => __('front.auth.email'),
            'password' => __('front.auth.password_plain'),
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => __('front.auth.mobile_invalid'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash('auth_modal', 'register');
        parent::failedValidation($validator);
    }
}
