<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class FrontCustomerPasswordResetCodeRequest extends FormRequest
{
    protected $errorBag = 'customerPasswordResetCode';

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'email' => mb_strtolower(trim((string) $this->input('email', ''))),
        ]);
    }

    public function rules(): array
    {
        return [
            'email' => ['required', 'email:rfc', 'max:255'],
        ];
    }

    public function attributes(): array
    {
        return [
            'email' => __('customer_auth.email'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash('auth_modal', 'forgotPassword');
        parent::failedValidation($validator);
    }
}
