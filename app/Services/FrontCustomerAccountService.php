<?php

namespace App\Services;

use App\Models\Customer;
use App\Models\Order;
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

        return Customer::query()
            ->where(function ($query) use ($login, $normalizedMobile): void {
                $query->where('account_no', $login);

                if ($normalizedMobile !== '') {
                    $query->orWhere('mobile', $normalizedMobile);
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
                    : __('front.auth.mobile_needs_activation'),
            ]);
        }

        $email = filled($data['email'] ?? null) ? strtolower(trim((string) $data['email'])) : null;

        if ($email && Customer::query()->where('email', $email)->exists()) {
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

    public function activate(array $data): Customer
    {
        $mobile = $this->normalizeMobile((string) $data['mobile']);
        $customer = Customer::query()->where('mobile', $mobile)->first();

        if (! $customer instanceof Customer) {
            throw ValidationException::withMessages([
                'mobile' => __('front.auth.activation_customer_not_found'),
            ]);
        }

        if (filled($customer->password)) {
            throw ValidationException::withMessages([
                'mobile' => __('front.auth.account_already_active'),
            ]);
        }

        $orderNo = strtoupper(trim((string) $data['order_no']));
        $ownsOrder = Order::query()
            ->where('order_no', $orderNo)
            ->where(function ($query) use ($customer, $mobile): void {
                $query->where('customer_id', $customer->getKey())
                    ->orWhere(function ($snapshotQuery) use ($mobile): void {
                        $snapshotQuery->whereNull('customer_id')
                            ->where('customer_mobile_snapshot', $mobile);
                    });
            })
            ->exists();

        if (! $ownsOrder) {
            throw ValidationException::withMessages([
                'order_no' => __('front.auth.activation_order_invalid'),
            ]);
        }

        DB::transaction(function () use ($customer, $data, $mobile): void {
            $customer->forceFill([
                'account_no' => $customer->account_no ?: $this->generateAccountNo(),
                'password' => $data['password'],
                'status' => 'active',
            ])->save();

            Order::query()
                ->whereNull('customer_id')
                ->where('customer_mobile_snapshot', $mobile)
                ->update(['customer_id' => $customer->getKey()]);
        });

        return $customer->refresh();
    }

    public function resetPasswordByOrderProof(array $data): Customer
    {
        $mobile = $this->normalizeMobile((string) $data['mobile']);
        $customer = Customer::query()->where('mobile', $mobile)->first();

        if (! $customer instanceof Customer) {
            throw ValidationException::withMessages([
                'mobile' => __('front.auth.reset_customer_not_found'),
            ]);
        }

        if ($customer->status !== 'active') {
            throw ValidationException::withMessages([
                'mobile' => __('front.auth.account_inactive'),
            ]);
        }

        $orderNo = strtoupper(trim((string) $data['order_no']));
        $ownsOrder = Order::query()
            ->where('order_no', $orderNo)
            ->where(function ($query) use ($customer, $mobile): void {
                $query->where('customer_id', $customer->getKey())
                    ->orWhere(function ($snapshotQuery) use ($mobile): void {
                        $snapshotQuery->whereNull('customer_id')
                            ->where('customer_mobile_snapshot', $mobile);
                    });
            })
            ->exists();

        if (! $ownsOrder) {
            throw ValidationException::withMessages([
                'order_no' => __('front.auth.reset_order_invalid'),
            ]);
        }

        DB::transaction(function () use ($customer, $data, $mobile): void {
            $customer->forceFill([
                'password' => $data['password'],
                'status' => 'active',
            ])->save();

            Order::query()
                ->whereNull('customer_id')
                ->where('customer_mobile_snapshot', $mobile)
                ->update(['customer_id' => $customer->getKey()]);
        });

        return $customer->refresh();
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
