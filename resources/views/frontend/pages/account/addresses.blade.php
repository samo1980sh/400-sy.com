@extends('frontend.pages.account.base')

@section('account_content')
    <div class="account-card mb_24">
        <h5 class="account-card-title">{{ __('front.account.add_address') }}</h5>
        <form method="POST" action="{{ route('front.account.addresses.store') }}">
            @csrf
            <div class="account-form-grid">
                <div>
                    <label class="account-label">{{ __('front.account.address_label') }}</label>
                    <input type="text" name="label" class="form-control" value="{{ old('label') }}" placeholder="{{ __('front.checkout.address_label_placeholder') }}">
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.address_type') }}</label>
                    <select name="address_type" class="form-select" required>
                        @foreach (['home', 'work', 'other'] as $type)
                            <option value="{{ $type }}" @selected(old('address_type', 'home') === $type)>{{ __('front.checkout.address_types.' . $type) }}</option>
                        @endforeach
                    </select>
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.contact_name') }}</label>
                    <input type="text" name="contact_name" class="form-control" value="{{ old('contact_name', $customer->name) }}" required>
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.mobile') }}</label>
                    <input type="tel" name="mobile" class="form-control" value="{{ old('mobile', $customer->mobile) }}" dir="ltr" required>
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.city') }}</label>
                    <input type="text" name="city" class="form-control" value="{{ old('city', $customer->city) }}" required>
                </div>
                <div>
                    <label class="account-label">{{ __('front.account.area') }}</label>
                    <input type="text" name="area" class="form-control" value="{{ old('area', $customer->area) }}" required>
                </div>
                <div class="full-width">
                    <label class="account-label">{{ __('front.account.address_line') }}</label>
                    <textarea name="address_line" class="form-control" rows="3" required>{{ old('address_line') }}</textarea>
                </div>
                <div class="full-width">
                    <label class="account-label">{{ __('front.account.notes') }}</label>
                    <textarea name="notes" class="form-control" rows="2">{{ old('notes') }}</textarea>
                </div>
                <div class="full-width form-check">
                    <input class="form-check-input" type="checkbox" name="is_default" value="1" id="new-address-default" @checked(old('is_default'))>
                    <label class="form-check-label" for="new-address-default">{{ __('front.account.make_default') }}</label>
                </div>
                <div class="full-width">
                    <button type="submit" class="tf-btn btn-fill radius-3">{{ __('front.account.save_address') }}</button>
                </div>
            </div>
        </form>
    </div>

    <div class="account-card">
        <h5 class="account-card-title">{{ __('front.account.saved_addresses') }}</h5>
        @if ($addresses->isEmpty())
            <p class="text-muted mb-0">{{ __('front.account.no_saved_address') }}</p>
        @else
            <div class="account-address-grid">
                @foreach ($addresses as $address)
                    <article class="account-address-card {{ $address->is_default ? 'is-default' : '' }}">
                        <div class="d-flex justify-content-between align-items-start gap-3 mb_12">
                            <div>
                                <h6 class="mb_4">{{ $address->label ?: __('front.checkout.address_types.' . $address->address_type) }}</h6>
                                <span class="account-badge">{{ __('front.checkout.address_types.' . $address->address_type) }}</span>
                            </div>
                            @if ($address->is_default)
                                <span class="account-badge is-success">{{ __('front.account.default_address') }}</span>
                            @endif
                        </div>
                        <div class="mb_6">{{ $address->contact_name }} — <span dir="ltr">{{ $address->mobile }}</span></div>
                        <div class="text-muted">{{ $address->city }}، {{ $address->area }}</div>
                        <div class="mt_6">{{ $address->address_line }}</div>

                        <div class="d-flex flex-wrap gap-2 mt_16">
                            @unless ($address->is_default)
                                <form method="POST" action="{{ route('front.account.addresses.default', $address) }}">
                                    @csrf
                                    @method('PATCH')
                                    <button type="submit" class="tf-btn btn-outline radius-3 py-2 px-3">{{ __('front.account.set_default') }}</button>
                                </form>
                            @endunless
                            <form method="POST" action="{{ route('front.account.addresses.destroy', $address) }}" onsubmit="return confirm(@json(__('front.account.confirm_delete_address')))">
                                @csrf
                                @method('DELETE')
                                <button type="submit" class="tf-btn btn-outline radius-3 py-2 px-3">{{ __('front.account.delete') }}</button>
                            </form>
                        </div>

                        <details class="mt_18 pt_16 border-top">
                            <summary>{{ __('front.account.edit_address') }}</summary>
                            <form method="POST" action="{{ route('front.account.addresses.update', $address) }}" class="mt_16">
                                @csrf
                                @method('PATCH')
                                <div class="account-form-grid">
                                    <div>
                                        <label class="account-label">{{ __('front.account.address_label') }}</label>
                                        <input type="text" name="label" class="form-control" value="{{ $address->label }}">
                                    </div>
                                    <div>
                                        <label class="account-label">{{ __('front.account.address_type') }}</label>
                                        <select name="address_type" class="form-select">
                                            @foreach (['home', 'work', 'other'] as $type)
                                                <option value="{{ $type }}" @selected($address->address_type === $type)>{{ __('front.checkout.address_types.' . $type) }}</option>
                                            @endforeach
                                        </select>
                                    </div>
                                    <div>
                                        <label class="account-label">{{ __('front.account.contact_name') }}</label>
                                        <input type="text" name="contact_name" class="form-control" value="{{ $address->contact_name }}" required>
                                    </div>
                                    <div>
                                        <label class="account-label">{{ __('front.account.mobile') }}</label>
                                        <input type="tel" name="mobile" class="form-control" value="{{ $address->mobile }}" dir="ltr" required>
                                    </div>
                                    <div>
                                        <label class="account-label">{{ __('front.account.city') }}</label>
                                        <input type="text" name="city" class="form-control" value="{{ $address->city }}" required>
                                    </div>
                                    <div>
                                        <label class="account-label">{{ __('front.account.area') }}</label>
                                        <input type="text" name="area" class="form-control" value="{{ $address->area }}" required>
                                    </div>
                                    <div class="full-width">
                                        <label class="account-label">{{ __('front.account.address_line') }}</label>
                                        <textarea name="address_line" class="form-control" rows="3" required>{{ $address->address_line }}</textarea>
                                    </div>
                                    <div class="full-width">
                                        <label class="account-label">{{ __('front.account.notes') }}</label>
                                        <textarea name="notes" class="form-control" rows="2">{{ $address->notes }}</textarea>
                                    </div>
                                    <input type="hidden" name="is_default" value="{{ $address->is_default ? 1 : 0 }}">
                                    <div class="full-width">
                                        <button type="submit" class="tf-btn btn-fill radius-3">{{ __('front.account.save_changes') }}</button>
                                    </div>
                                </div>
                            </form>
                        </details>
                    </article>
                @endforeach
            </div>
        @endif
    </div>
@endsection
