<?php

namespace App\Services;

use App\Models\Customer;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Str;
use Illuminate\Validation\ValidationException;

class FrontCustomerAccountService
{
    public function normalizeMobile(string $mobile): string
    {
        $mobile = strtr($mobile, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
        ]);

        return preg_replace('/[\s\-()]+/', '', trim($mobile)) ?: '';
    }

    public function findForLogin(string $login): ?Customer
    {
        $login = trim($login);
        $normalizedMobile = $this->normalizeMobile($login);
        $normalizedEmail = mb_strtolower($login);

        return Customer::query()
            ->where(function ($query) use ($login, $normalizedMobile, $normalizedEmail): void {
                $query->where('account_no', $login);

                if ($normalizedMobile !== '') {
                    $query->orWhere('mobile', $normalizedMobile);
                }

                if ($normalizedEmail !== '') {
                    $query->orWhere('email', $normalizedEmail);
                }
            })
            ->first();
    }

    public function register(array $data): Customer
    {
        $mobile = $this->normalizeMobile((string) $data['mobile']);
        $existing = Customer::query()->where('mobile', $mobile)->first();

        if ($existing instanceof Customer) {
            throw ValidationException::withMessages([
                'mobile' => filled($existing->password)
                    ? __('front.auth.mobile_already_registered')
                    : __('customer_auth.mobile_needs_activation'),
            ]);
        }

        $email = mb_strtolower(trim((string) $data['email']));

        if (Customer::query()->where('email', $email)->exists()) {
            throw ValidationException::withMessages([
                'email' => __('front.auth.email_already_registered'),
            ]);
        }

        return DB::transaction(fn (): Customer => Customer::create([
            'account_no' => $this->generateAccountNo(),
            'name' => trim((string) $data['name']),
            'birth_date' => $data['birth_date'],
            'nationality' => trim((string) $data['nationality']),
            'mobile' => $mobile,
            'secondary_mobile' => filled($data['secondary_mobile'] ?? null)
                ? $this->normalizeMobile((string) $data['secondary_mobile'])
                : null,
            'gender' => $data['gender'],
            'city' => trim((string) $data['city']),
            'area' => trim((string) $data['area']),
            'job_title' => filled($data['job_title'] ?? null) ? trim((string) $data['job_title']) : null,
            'marital_status' => filled($data['marital_status'] ?? null) ? $data['marital_status'] : null,
            'email' => $email,
            'password' => $data['password'],
            'status' => 'active',
        ]));
    }

    public function passwordMatches(Customer $customer, string $password): bool
    {
        return filled($customer->password) && Hash::check($password, $customer->password);
    }

    public function generateAccountNo(): string
    {
        do {
            $accountNo = 'WEB-' . now()->format('ym') . '-' . strtoupper(Str::random(6));
        } while (Customer::query()->where('account_no', $accountNo)->exists());

        return $accountNo;
    }
}
