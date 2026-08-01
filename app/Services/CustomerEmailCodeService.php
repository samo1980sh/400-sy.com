<?php

namespace App\Services;

use App\Mail\CustomerEmailCodeMail;
use App\Models\Customer;
use App\Models\CustomerEmailCode;
use App\Models\Order;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Mail;
use Illuminate\Validation\ValidationException;
use Throwable;

class CustomerEmailCodeService
{
    public const PURPOSE_ACTIVATION = 'activation';

    public const PURPOSE_PASSWORD_RESET = 'password_reset';

    public const CODE_EXPIRES_MINUTES = 10;

    public const RESEND_COOLDOWN_SECONDS = 60;

    public const MAX_ATTEMPTS = 5;

    public function sendActivationCode(string $email, ?string $ip = null): bool
    {
        $email = $this->normalizeEmail($email);
        $customer = Customer::query()->where('email', $email)->first();

        if (! $customer instanceof Customer || $customer->status !== 'active' || filled($customer->password)) {
            return false;
        }

        $this->issue(
            customer: $customer,
            email: $email,
            purpose: self::PURPOSE_ACTIVATION,
            ip: $ip,
        );

        return true;
    }

    public function sendPasswordResetCode(string $email, ?string $ip = null): bool
    {
        $email = $this->normalizeEmail($email);
        $customer = Customer::query()->where('email', $email)->first();

        if (! $customer instanceof Customer || $customer->status !== 'active' || blank($customer->password)) {
            return false;
        }

        $this->issue(
            customer: $customer,
            email: $email,
            purpose: self::PURPOSE_PASSWORD_RESET,
            ip: $ip,
        );

        return true;
    }

    public function activateAccount(string $email, string $code, string $password): Customer
    {
        return $this->verifyAndSetPassword(
            email: $email,
            code: $code,
            password: $password,
            purpose: self::PURPOSE_ACTIVATION,
            requireExistingPassword: false,
        );
    }

    public function resetPassword(string $email, string $code, string $password): Customer
    {
        return $this->verifyAndSetPassword(
            email: $email,
            code: $code,
            password: $password,
            purpose: self::PURPOSE_PASSWORD_RESET,
            requireExistingPassword: true,
        );
    }

    protected function issue(Customer $customer, string $email, string $purpose, ?string $ip): void
    {
        $latest = CustomerEmailCode::query()
            ->where('email', $email)
            ->where('purpose', $purpose)
            ->latest('id')
            ->first();

        if ($latest instanceof CustomerEmailCode && $latest->created_at->gt(now()->subSeconds(self::RESEND_COOLDOWN_SECONDS))) {
            $elapsed = max(0, now()->getTimestamp() - $latest->created_at->getTimestamp());
            $remaining = max(1, self::RESEND_COOLDOWN_SECONDS - $elapsed);

            throw ValidationException::withMessages([
                'email' => __('customer_auth.resend_wait', ['seconds' => $remaining]),
            ]);
        }

        $code = str_pad((string) random_int(0, 999999), 6, '0', STR_PAD_LEFT);

        $record = DB::transaction(function () use ($customer, $email, $purpose, $ip, $code): CustomerEmailCode {
            CustomerEmailCode::query()
                ->where('email', $email)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return CustomerEmailCode::query()->create([
                'customer_id' => $customer->getKey(),
                'email' => $email,
                'purpose' => $purpose,
                'code_hash' => $this->digest($email, $purpose, $code),
                'attempts' => 0,
                'expires_at' => now()->addMinutes(self::CODE_EXPIRES_MINUTES),
                'requested_ip' => $ip,
            ]);
        });

        try {
            Mail::to($email)->send(new CustomerEmailCodeMail(
                code: $code,
                purpose: $purpose,
                expiresMinutes: self::CODE_EXPIRES_MINUTES,
            ));
        } catch (Throwable $exception) {
            $record->delete();

            throw $exception;
        }

        CustomerEmailCode::query()
            ->where('created_at', '<', now()->subDays(7))
            ->delete();
    }

    protected function verifyAndSetPassword(
        string $email,
        string $code,
        string $password,
        string $purpose,
        bool $requireExistingPassword,
    ): Customer {
        $email = $this->normalizeEmail($email);
        $code = $this->normalizeCode($code);

        $outcome = DB::transaction(function () use ($email, $code, $password, $purpose, $requireExistingPassword): array {
            $record = CustomerEmailCode::query()
                ->where('email', $email)
                ->where('purpose', $purpose)
                ->whereNull('consumed_at')
                ->latest('id')
                ->lockForUpdate()
                ->first();

            if (! $record instanceof CustomerEmailCode || $record->expires_at->isPast()) {
                return ['error' => 'invalid'];
            }

            if (! hash_equals((string) $record->code_hash, $this->digest($email, $purpose, $code))) {
                return $this->recordFailedAttempt($record);
            }

            $customer = Customer::query()
                ->whereKey($record->customer_id)
                ->where('email', $email)
                ->lockForUpdate()
                ->first();

            if (! $customer instanceof Customer) {
                return ['error' => 'invalid'];
            }

            if ($customer->status !== 'active') {
                return ['error' => 'inactive'];
            }

            if ($requireExistingPassword && blank($customer->password)) {
                return ['error' => 'needs_activation'];
            }

            if (! $requireExistingPassword && filled($customer->password)) {
                return ['error' => 'already_active'];
            }

            $customer->forceFill([
                'password' => $password,
                'email_verified_at' => $customer->email_verified_at ?: now(),
                'status' => 'active',
            ])->save();

            Order::query()
                ->whereNull('customer_id')
                ->where('customer_mobile_snapshot', $customer->mobile)
                ->update(['customer_id' => $customer->getKey()]);

            CustomerEmailCode::query()
                ->where('customer_id', $customer->getKey())
                ->whereNull('consumed_at')
                ->update(['consumed_at' => now()]);

            return ['customer' => $customer];
        });

        $this->throwOutcomeError($outcome);

        /** @var Customer $customer */
        $customer = $outcome['customer'];

        return $customer->refresh();
    }

    /**
     * @return array{error:string}
     */
    protected function recordFailedAttempt(CustomerEmailCode $record): array
    {
        $attempts = $record->attempts + 1;

        $record->forceFill([
            'attempts' => $attempts,
            'consumed_at' => $attempts >= self::MAX_ATTEMPTS ? now() : null,
        ])->save();

        return [
            'error' => $attempts >= self::MAX_ATTEMPTS ? 'attempts' : 'invalid',
        ];
    }

    /**
     * @param array<string, mixed> $outcome
     */
    protected function throwOutcomeError(array $outcome): void
    {
        $error = $outcome['error'] ?? null;

        if ($error === null) {
            return;
        }

        $message = match ($error) {
            'attempts' => __('customer_auth.code_attempts_exceeded'),
            'inactive' => __('customer_auth.account_inactive'),
            'already_active' => __('customer_auth.account_already_active'),
            'needs_activation' => __('customer_auth.account_needs_activation'),
            default => __('customer_auth.code_invalid_or_expired'),
        };

        throw ValidationException::withMessages([
            'code' => $message,
        ]);
    }

    protected function digest(string $email, string $purpose, string $code): string
    {
        return hash_hmac('sha256', $email . '|' . $purpose . '|' . $code, (string) config('app.key'));
    }

    protected function normalizeEmail(string $email): string
    {
        return mb_strtolower(trim($email));
    }

    protected function normalizeCode(string $code): string
    {
        $code = strtr($code, [
            '٠' => '0', '١' => '1', '٢' => '2', '٣' => '3', '٤' => '4',
            '٥' => '5', '٦' => '6', '٧' => '7', '٨' => '8', '٩' => '9',
            '۰' => '0', '۱' => '1', '۲' => '2', '۳' => '3', '۴' => '4',
            '۵' => '5', '۶' => '6', '۷' => '7', '۸' => '8', '۹' => '9',
        ]);

        return preg_replace('/\D+/', '', trim($code)) ?: '';
    }
}
