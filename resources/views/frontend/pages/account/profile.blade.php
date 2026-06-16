@extends('frontend.pages.account.base')

@section('account_content')
    <div class="account-card mb_24">
        <h5 class="account-card-title">{{ __('front.account.profile_information') }}</h5>
        <form method="POST" action="{{ route('front.account.profile.update') }}">
            @csrf
            @method('PATCH')
            <div class="account-form-grid">
                <div>
                    <label class="account-label">{{ __('front.account.name') }}</label>
                    <input type="text" name="name" class="form-control" value="{{ old('name', $customer->name) }}" required>
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.primary_mobile') }}</label>
                    <input type="text" class="form-control" value="{{ $customer->mobile }}" dir="ltr" readonly>
                    <small class="text-muted">{{ __('front.account.primary_mobile_locked') }}</small>
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.email') }}</label>
                    <input type="email" name="email" class="form-control" value="{{ old('email', $customer->email) }}" dir="ltr">
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.secondary_mobile') }}</label>
                    <input type="tel" name="secondary_mobile" class="form-control" value="{{ old('secondary_mobile', $customer->secondary_mobile) }}" dir="ltr">
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.birth_date') }}</label>
                    <input type="date" name="birth_date" class="form-control" value="{{ old('birth_date', optional($customer->birth_date)->format('Y-m-d')) }}">
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.account_number') }}</label>
                    <input type="text" class="form-control" value="{{ $customer->account_no }}" dir="ltr" readonly>
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.city') }}</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $customer->city) }}">
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.area') }}</label>
                    <input type="text" name="area" class="form-control" value="{{ old('area', $customer->area) }}">
                </div>
                <div class="full-width">
                    <button type="submit" class="tf-btn btn-fill radius-3">{{ __('front.account.save_changes') }}</button>
                </div>
            </div>
        </form>
    </div>

    <div class="account-card">
        <h5 class="account-card-title">{{ __('front.account.change_password') }}</h5>
        <form method="POST" action="{{ route('front.account.password.update') }}">
            @csrf
            @method('PATCH')
            <div class="account-form-grid">
                <div class="full-width">
                    <label class="account-label">{{ __('front.account.current_password') }}</label>
                    <input type="password" name="current_password" class="form-control" autocomplete="current-password" required>
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.new_password') }}</label>
                    <input type="password" name="password" class="form-control" autocomplete="new-password" required>
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.password_confirmation') }}</label>
                    <input type="password" name="password_confirmation" class="form-control" autocomplete="new-password" required>
                </div>
                <div class="full-width">
                    <button type="submit" class="tf-btn btn-fill radius-3">{{ __('front.account.update_password') }}</button>
                </div>
            </div>
        </form>
    </div>
@endsection
