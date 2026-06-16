<?php

namespace App\Http\Requests;

use Illuminate\Contracts\Validation\Validator;
use Illuminate\Foundation\Http\FormRequest;

class FrontCustomerActivateRequest extends FormRequest
{
    protected $errorBag = 'customerActivate';

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
            'mobile' => preg_replace('/[\s\-()]+/', '', trim($mobile)) ?: '',
            'order_no' => strtoupper(trim((string) $this->input('order_no', ''))),
        ]);
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'min:7', 'max:30', 'regex:/^\+?[0-9]+$/'],
            'order_no' => ['required', 'string', 'max:100'],
            'password' => ['required', 'string', 'min:8', 'max:255', 'confirmed'],
        ];
    }

    public function attributes(): array
    {
        return [
            'mobile' => __('front.auth.mobile_number'),
            'order_no' => __('front.auth.previous_order_no'),
            'password' => __('front.auth.password_plain'),
        ];
    }

    protected function failedValidation(Validator $validator): void
    {
        session()->flash('auth_modal', 'activateAccount');
        parent::failedValidation($validator);
    }
}
