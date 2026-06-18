<?php

namespace App\Http\Requests;

use Illuminate\Foundation\Http\FormRequest;

class SendContactMessageRequest extends FormRequest
{
    protected $errorBag = 'contact';

    protected $dontFlash = [
        'name',
        'email',
        'phone',
        'subject',
        'message',
        'website',
    ];

    public function authorize(): bool
    {
        return true;
    }

    protected function prepareForValidation(): void
    {
        $phone = strtr((string) $this->input('phone', ''), [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        $this->merge([
            'name' => trim(preg_replace('/[\r\n]+/', ' ', (string) $this->input('name', '')) ?: ''),
            'email' => strtolower(trim((string) $this->input('email', ''))),
            'phone' => trim($phone),
            'subject' => trim(preg_replace('/[\r\n]+/', ' ', (string) $this->input('subject', '')) ?: ''),
            'message' => trim((string) $this->input('message', '')),
        ]);
    }

    public function rules(): array
    {
        return [
            'name' => ['required', 'string', 'min:2', 'max:120'],
            'email' => ['required', 'email:rfc', 'max:255'],
            'phone' => ['required', 'string', 'min:7', 'max:30', 'regex:/^[0-9+\s().-]+$/u'],
            'subject' => ['required', 'string', 'min:3', 'max:160'],
            'message' => ['required', 'string', 'min:10', 'max:5000'],
            'website' => ['prohibited'],
        ];
    }

    public function attributes(): array
    {
        return [
            'name' => __('front.contact.name'),
            'email' => __('front.contact.email'),
            'phone' => __('front.contact.phone'),
            'subject' => __('front.contact.subject'),
            'message' => __('front.contact.message'),
            'website' => __('front.contact.website'),
        ];
    }

    public function messages(): array
    {
        return [
            'phone.regex' => __('front.contact.phone_invalid'),
            'website.prohibited' => __('front.contact.invalid_submission'),
        ];
    }
}
