<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class PreviewFrontCouponRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $this->merge([
            'coupon_code' => mb_strtoupper(trim((string) $this->input('coupon_code', ''))),
        ]);
    }

    public function rules(): array
    {
        return [
            'coupon_code' => ['required', 'string', 'max:100'],
        ];
    }

    public function attributes(): array
    {
        return [
            'coupon_code' => __('front.checkout.coupon_code'),
        ];
    }
}
