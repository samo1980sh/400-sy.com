<?php

namespace App\Http\Requests;

use App\Models\Customer;
use Illuminate\Foundation\Http\FormRequest;
use Illuminate\Validation\Rule;
use Illuminate\Validation\Validator;

class StoreFrontOrderRequest extends FormRequest
{
    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $mobile = strtr((string) $this->input('mobile', ''), [
            '٠' => '0',
            '١' => '1',
            '٢' => '2',
            '٣' => '3',
            '٤' => '4',
            '٥' => '5',
            '٦' => '6',
            '٧' => '7',
            '٨' => '8',
            '٩' => '9',
        ]);

        $this->merge([
            'full_name' => trim((string) $this->input('full_name', '')),
            'mobile' => preg_replace('/[\s\-()]+/', '', trim($mobile)) ?: '',
            'email' => filled($this->input('email'))
                ? strtolower(trim((string) $this->input('email')))
                : null,
            'city' => trim((string) $this->input('city', '')),
            'area' => trim((string) $this->input('area', '')),
            'address_line' => trim((string) $this->input('address_line', '')),
            'address_label' => filled($this->input('address_label'))
                ? trim((string) $this->input('address_label'))
                : null,
            'notes' => filled($this->input('notes'))
                ? trim((string) $this->input('notes'))
                : null,
        ]);
    }

    public function rules(): array
    {
        return [
            'full_name' => ['required', 'string', 'min:2', 'max:255'],
            'mobile' => ['required', 'string', 'min:7', 'max:30', 'regex:/^\+?[0-9]+$/'],
            'email' => ['nullable', 'email:rfc', 'max:255'],
            'city' => ['required', 'string', 'max:255'],
            'area' => ['required', 'string', 'max:255'],
            'address_line' => ['required', 'string', 'min:5', 'max:1000'],
            'address_type' => ['required', Rule::in(['home', 'work', 'other'])],
            'address_label' => ['nullable', 'string', 'max:255'],
            'shipping_method_id' => [
                'required',
                'integer',
                Rule::exists('shipping_methods', 'id')->where(fn ($query) => $query->where('active', true)),
            ],
            'payment_method' => [
                'required',
                'string',
                'max:100',
                Rule::exists('payment_methods', 'code')->where(fn ($query) => $query->where('active', true)),
            ],
            'notes' => ['nullable', 'string', 'max:2000'],
            'terms' => ['accepted'],
        ];
    }

    public function after(): array
    {
        return [
            function (Validator $validator): void {
                $email = $this->input('email');
                $mobile = $this->input('mobile');

                if (! filled($email) || ! filled($mobile)) {
                    return;
                }

                $emailCustomer = Customer::query()
                    ->where('email', $email)
                    ->first(['id', 'mobile']);

                if ($emailCustomer instanceof Customer && $emailCustomer->mobile !== $mobile) {
                    $validator->errors()->add('email', __('front.checkout.email_in_use'));
                }
            },
        ];
    }

    public function attributes(): array
    {
        return [
            'full_name' => __('front.checkout.full_name'),
            'mobile' => __('front.checkout.mobile'),
            'email' => __('front.checkout.email'),
            'city' => __('front.checkout.city'),
            'area' => __('front.checkout.area'),
            'address_line' => __('front.checkout.address_line'),
            'address_type' => __('front.checkout.address_type'),
            'address_label' => __('front.checkout.address_label'),
            'shipping_method_id' => __('front.checkout.shipping_method'),
            'payment_method' => __('front.checkout.payment_method'),
            'notes' => __('front.checkout.notes'),
            'terms' => __('front.checkout.terms'),
        ];
    }

    public function messages(): array
    {
        return [
            'mobile.regex' => __('front.checkout.mobile_invalid'),
            'terms.accepted' => __('front.checkout.terms_required'),
        ];
    }
}
