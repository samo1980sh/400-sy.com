<?php

namespace App\Http\Requests;

use App\Services\FrontCustomerAccountService;
use Illuminate\Foundation\Http\FormRequest;

class FrontCustomerForgotPasswordRequest extends FormRequest
{
    public function authorize(): bool
    {
        return ! auth('customer')->check();
    }

    public function rules(): array
    {
        return [
            'mobile' => ['required', 'string', 'max:30'],
            'order_no' => ['required', 'string', 'max:50'],
            'password' => ['required', 'string', 'min:8', 'confirmed'],
        ];
    }

    protected function prepareForValidation(): void
    {
        $accounts = app(FrontCustomerAccountService::class);

        $this->merge([
            'mobile' => $accounts->normalizeMobile((string) $this->input('mobile')),
            'order_no' => strtoupper(trim((string) $this->input('order_no'))),
        ]);
    }

    public function attributes(): array
    {
        return [
            'mobile' => __('front.auth.mobile_number'),
            'order_no' => __('front.auth.previous_order_no'),
            'password' => __('front.auth.new_password'),
        ];
    }
}