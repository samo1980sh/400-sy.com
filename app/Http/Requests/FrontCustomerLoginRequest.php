<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Contracts\Validation\Validator;

class FrontCustomerLoginRequest extends FormRequest
{
    protected $errorBag = 'customerLogin';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'login' => trim((string) $this->input('login', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'login' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'login' => __('front.auth.login_identifier'),
            'password' => __('front.auth.password_plain'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash('auth_modal', 'login');
        parent::failedValidation($validator);
    }
}
