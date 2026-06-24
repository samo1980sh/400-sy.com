@guest('customer')
    
    <style>
        .customer-register-modal .modal-dialog { max-width: 860px; }
        .customer-register-modal .modal-content { border-radius: 14px; }
        .customer-register-form { max-height: 76vh; overflow-y: auto; padding-inline-end: 4px; }
        .customer-register-grid { display: grid; grid-template-columns: repeat(2, minmax(0, 1fr)); gap: 16px 22px; }
        .customer-register-field { display: flex; flex-direction: column; gap: 7px; }
        .customer-register-field.full-width { grid-column: 1 / -1; }
        .customer-register-label { font-size: 14px; font-weight: 700; color: #1f1f1f; margin: 0; }
        .customer-register-required { color: #d72626; margin-inline-start: 4px; }
        .customer-register-control { width: 100%; min-height: 48px; border: 1px solid #dedede; border-radius: 6px; padding: 10px 14px; background: #fff; color: #222; font-size: 14px; }
        .customer-register-control::placeholder { color: #9a9a9a; }
        .customer-register-control[dir="ltr"] { text-align: left; }
        .customer-register-separator { grid-column: 1 / -1; border: 0; border-top: 1px solid #eeeeee; margin: 2px 0; }
        .customer-password-rules { grid-column: 1 / -1; border: 1px solid #e5e5e5; border-radius: 8px; padding: 14px 16px; background: #fafafa; }
        .customer-password-rules-title { font-weight: 700; margin-bottom: 10px; }
        .customer-password-rules-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px 14px; margin: 0; padding: 0; list-style: none; font-size: 13px; color: #555; }
        .customer-password-rules-list li::before { content: '✓'; display: inline-flex; align-items: center; justify-content: center; width: 17px; height: 17px; border-radius: 50%; background: #219653; color: #fff; font-size: 11px; margin-inline-end: 7px; }
        .customer-register-terms { grid-column: 1 / -1; border-top: 1px solid #eeeeee; padding-top: 16px; color: #555; font-size: 14px; }
        .customer-register-actions { grid-column: 1 / -1; display: grid; grid-template-columns: 1fr auto; gap: 18px; align-items: center; }
        .customer-register-submit { min-width: 230px; }
        .customer-register-login-link { color: #b98619; font-weight: 700; text-decoration: underline; }
        @media (max-width: 767.98px) {
            .customer-register-modal .modal-dialog { max-width: calc(100% - 16px); margin: 8px auto; }
            .customer-register-form { max-height: 82vh; }
            .customer-register-grid { grid-template-columns: 1fr; gap: 14px; }
            .customer-password-rules-list { grid-template-columns: 1fr; }
            .customer-register-actions { grid-template-columns: 1fr; }
            .customer-register-submit { width: 100%; min-width: 0; }
        }
    </style>
<div class="modal modalCentered fade form-sign-in modal-part-content" id="login" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="header">
                    <div class="demo-title">{{ __('front.auth.login_title') }}</div>
                    <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
                </div>
                <div class="tf-login-form">
                    <form method="POST" action="{{ route('front.customer.login') }}" accept-charset="utf-8">
                        @csrf

                        @if ($errors->customerLogin->any())
                            <div class="alert alert-danger mb_20">
                                @foreach ($errors->customerLogin->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="tf-field style-1">
                            <input
                                class="tf-field-input tf-input"
                                placeholder=" "
                                type="text"
                                name="login"
                                value="{{ old('login') }}"
                                autocomplete="username"
                                dir="ltr"
                                required
                            >
                            <label class="tf-field-label">{{ __('front.auth.login_identifier') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input
                                class="tf-field-input tf-input"
                                placeholder=" "
                                type="password"
                                name="password"
                                autocomplete="current-password"
                                required
                            >
                            <label class="tf-field-label">{{ __('front.auth.password_plain') }}</label>
                        </div>
                        <div class="d-flex justify-content-between gap-3 flex-wrap">
                            <a href="#forgotPassword" data-bs-toggle="modal" class="btn-link link">{{ __('front.auth.forgot_password') }}</a>
                            <a href="#activateAccount" data-bs-toggle="modal" class="btn-link link">{{ __('front.auth.activate_previous_customer') }}</a>
                        </div>
                        <div class="bottom">
                            <div class="w-100">
                                <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                                    <span>{{ __('front.auth.log_in') }}</span>
                                </button>
                            </div>
                            <div class="w-100">
                                <a href="#register" data-bs-toggle="modal" class="btn-link fw-6 w-100 link">
                                    {{ __('front.auth.new_customer_create_account') }}
                                    <i class="icon icon-arrow1-top-left"></i>
                                </a>
                            </div>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modalCentered fade form-sign-in modal-part-content customer-register-modal" id="register" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="header">
                    <div class="demo-title">{{ __('front.auth.register_title') }}</div>
                    <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
                </div>
                <div class="tf-login-form">
                    <form method="POST" action="{{ route('front.customer.register') }}">
                        @csrf

                        @if ($errors->customerRegister->any())
                            <div class="alert alert-danger mb_20">
                                @foreach ($errors->customerRegister->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        
                        @php
                            $nationalityOptions = ['syrian', 'egyptian', 'jordanian', 'iraqi', 'lebanese', 'palestinian', 'saudi', 'emirati', 'kuwaiti', 'qatari', 'other'];
                            $maritalStatusOptions = ['single', 'married', 'divorced', 'widowed'];
                        @endphp

                        <div class="customer-register-grid" dir="{{ app()->getLocale() === 'ar' ? 'rtl' : 'ltr' }}">
                            <div class="customer-register-field full-width">
                                <label class="customer-register-label" for="register_name">{{ __('front.auth.full_name_required') }}<span class="customer-register-required">*</span></label>
                                <input id="register_name" class="customer-register-control" type="text" name="name" value="{{ old('name') }}" placeholder="{{ __('front.auth.name_placeholder') }}" autocomplete="name" required>
                            </div>

                            <div class="customer-register-field full-width">
                                <label class="customer-register-label" for="register_birth_date">{{ __('front.auth.birth_date') }}<span class="customer-register-required">*</span></label>
                                <input id="register_birth_date" class="customer-register-control" type="date" name="birth_date" value="{{ old('birth_date') }}" required>
                            </div>

                            <div class="customer-register-field">
                                <label class="customer-register-label" for="register_nationality">{{ __('front.auth.nationality') }}<span class="customer-register-required">*</span></label>
                                <select id="register_nationality" class="customer-register-control" name="nationality" required>
                                    <option value="">{{ __('front.auth.select_nationality') }}</option>
                                    @foreach ($nationalityOptions as $nationality)
                                        <option value="{{ $nationality }}" @selected(old('nationality') === $nationality)>{{ __('front.auth.nationalities.' . $nationality) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="customer-register-field">
                                <label class="customer-register-label" for="register_gender">{{ __('front.auth.gender') }}<span class="customer-register-required">*</span></label>
                                <select id="register_gender" class="customer-register-control" name="gender" required>
                                    <option value="">{{ __('front.auth.select_gender') }}</option>
                                    <option value="male" @selected(old('gender') === 'male')>{{ __('front.auth.gender_male') }}</option>
                                    <option value="female" @selected(old('gender') === 'female')>{{ __('front.auth.gender_female') }}</option>
                                </select>
                            </div>

                            <div class="customer-register-field full-width">
                                <label class="customer-register-label" for="register_mobile">{{ __('front.auth.mobile_number_without_zero') }}<span class="customer-register-required">*</span></label>
                                <input id="register_mobile" class="customer-register-control" type="tel" name="mobile" value="{{ old('mobile') }}" placeholder="{{ __('front.auth.mobile_without_zero_placeholder') }}" autocomplete="tel" dir="ltr" inputmode="numeric" maxlength="9" required>
                            </div>

                            <div class="customer-register-field">
                                <label class="customer-register-label" for="register_city">{{ __('front.auth.city') }}<span class="customer-register-required">*</span></label>
                                <input id="register_city" class="customer-register-control" type="text" name="city" value="{{ old('city') }}" placeholder="{{ __('front.auth.city_placeholder') }}" required>
                            </div>

                            <div class="customer-register-field">
                                <label class="customer-register-label" for="register_area">{{ __('front.auth.area') }}<span class="customer-register-required">*</span></label>
                                <input id="register_area" class="customer-register-control" type="text" name="area" value="{{ old('area') }}" placeholder="{{ __('front.auth.area_placeholder') }}" required>
                            </div>

                            <div class="customer-register-field">
                                <label class="customer-register-label" for="register_job_title">{{ __('front.auth.job_title_optional') }}</label>
                                <input id="register_job_title" class="customer-register-control" type="text" name="job_title" value="{{ old('job_title') }}" placeholder="{{ __('front.auth.job_title_placeholder') }}">
                            </div>

                            <div class="customer-register-field">
                                <label class="customer-register-label" for="register_secondary_mobile">{{ __('front.auth.secondary_mobile_optional') }}</label>
                                <input id="register_secondary_mobile" class="customer-register-control" type="tel" name="secondary_mobile" value="{{ old('secondary_mobile') }}" placeholder="{{ __('front.auth.secondary_mobile_placeholder') }}" dir="ltr" inputmode="numeric" maxlength="9">
                            </div>

                            <div class="customer-register-field full-width">
                                <label class="customer-register-label" for="register_marital_status">{{ __('front.auth.marital_status') }}</label>
                                <select id="register_marital_status" class="customer-register-control" name="marital_status">
                                    <option value="">{{ __('front.auth.select_marital_status_optional') }}</option>
                                    @foreach ($maritalStatusOptions as $status)
                                        <option value="{{ $status }}" @selected(old('marital_status') === $status)>{{ __('front.auth.marital_statuses.' . $status) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div class="customer-register-field full-width">
                                <label class="customer-register-label" for="register_email">{{ __('front.auth.email_optional') }}</label>
                                <input id="register_email" class="customer-register-control" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('front.auth.email_placeholder') }}" autocomplete="email" dir="ltr">
                            </div>

                            <hr class="customer-register-separator">

                            <div class="customer-register-field">
                                <label class="customer-register-label" for="register_password">{{ __('front.auth.password_plain') }}<span class="customer-register-required">*</span></label>
                                <input id="register_password" class="customer-register-control" type="password" name="password" placeholder="{{ __('front.auth.password_placeholder') }}" autocomplete="new-password" required>
                            </div>

                            <div class="customer-register-field">
                                <label class="customer-register-label" for="register_password_confirmation">{{ __('front.auth.password_confirmation') }}<span class="customer-register-required">*</span></label>
                                <input id="register_password_confirmation" class="customer-register-control" type="password" name="password_confirmation" placeholder="{{ __('front.auth.password_confirmation_placeholder') }}" autocomplete="new-password" required>
                            </div>

                            <div class="customer-password-rules">
                                <div class="customer-password-rules-title">{{ __('front.auth.password_rules_title') }}</div>
                                <ul class="customer-password-rules-list">
                                    <li>{{ __('front.auth.password_rule_min') }}</li>
                                    <li>{{ __('front.auth.password_rule_lower') }}</li>
                                    <li>{{ __('front.auth.password_rule_upper') }}</li>
                                    <li>{{ __('front.auth.password_rule_number') }}</li>
                                    <li>{{ __('front.auth.password_rule_symbol') }}</li>
                                </ul>
                            </div>

                            <div class="customer-register-terms">{{ __('front.auth.register_terms_notice') }}</div>

                            <div class="customer-register-actions">
                                <div>
                                    {{ __('front.auth.register_login_prompt') }}
                                    <a href="#login" data-bs-toggle="modal" class="customer-register-login-link">{{ __('front.auth.register_login_here') }}</a>
                                </div>
                                <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 justify-content-center customer-register-submit">
                                    <span>{{ __('front.auth.register_button') }}</span>
                                </button>
                            </div>
                        </div>
</form>
                </div>
            </div>
        </div>
    </div>
    <div class="modal modalCentered fade form-sign-in modal-part-content" id="activateAccount" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="header">
                    <div class="demo-title">{{ __('front.auth.activation_title') }}</div>
                    <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
                </div>
                <div class="tf-login-form">
                    <form method="POST" action="{{ route('front.customer.activate') }}">
                        @csrf

                        <p class="text-muted mb_20">{{ __('front.auth.activation_help') }}</p>

                        @if ($errors->customerActivate->any())
                            <div class="alert alert-danger mb_20">
                                @foreach ($errors->customerActivate->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="tel" name="mobile" value="{{ old('mobile') }}" dir="ltr" required>
                            <label class="tf-field-label">{{ __('front.auth.mobile_number') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="text" name="order_no" value="{{ old('order_no') }}" dir="ltr" required>
                            <label class="tf-field-label">{{ __('front.auth.previous_order_no') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password" autocomplete="new-password" required>
                            <label class="tf-field-label">{{ __('front.auth.new_password') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password_confirmation" autocomplete="new-password" required>
                            <label class="tf-field-label">{{ __('front.auth.password_confirmation') }}</label>
                        </div>
                        <div class="bottom">
                            <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                                {{ __('front.auth.activate_button') }}
                            </button>
                            <a href="#login" data-bs-toggle="modal" class="btn-link fw-6 w-100 link">{{ __('front.auth.back_to_login') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modalCentered fade form-sign-in modal-part-content" id="forgotPassword" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="header">
                    <div class="demo-title">{{ __('front.auth.reset_password') }}</div>
                    <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
                </div>
                <div class="tf-login-form">
                    <form method="POST" action="{{ route('front.customer.forgot-password') }}">
                        @csrf

                        <p class="text-muted mb_20">{{ __('front.auth.reset_password_help') }}</p>

                        @if ($errors->customerForgotPassword->any())
                            <div class="alert alert-danger mb_20">
                                @foreach ($errors->customerForgotPassword->all() as $error)
                                    <div>{{ $error }}</div>
                                @endforeach
                            </div>
                        @endif

                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="tel" name="mobile" value="{{ old('mobile') }}" dir="ltr" required>
                            <label class="tf-field-label">{{ __('front.auth.mobile_number') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="text" name="order_no" value="{{ old('order_no') }}" dir="ltr" required>
                            <label class="tf-field-label">{{ __('front.auth.previous_order_no') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password" autocomplete="new-password" required>
                            <label class="tf-field-label">{{ __('front.auth.new_password') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password_confirmation" autocomplete="new-password" required>
                            <label class="tf-field-label">{{ __('front.auth.password_confirmation') }}</label>
                        </div>
                        <div class="bottom">
                            <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                                {{ __('front.auth.reset_password_button') }}
                            </button>
                            <a href="#login" data-bs-toggle="modal" class="btn-link fw-6 w-100 link">{{ __('front.auth.back_to_login') }}</a>
                        </div>
                    </form>
                </div>
            </div>
        </div>
    </div>

    @if (session('auth_modal'))
        <script>
            document.addEventListener('DOMContentLoaded', function () {
                var modalElement = document.getElementById(@json(session('auth_modal')));
                if (modalElement && window.bootstrap) {
                    window.bootstrap.Modal.getOrCreateInstance(modalElement).show();
                }
            });
        </script>
    @endif
@endguest
