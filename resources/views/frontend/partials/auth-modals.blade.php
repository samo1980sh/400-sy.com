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
        .customer-password-rules-list { display: grid; grid-template-columns: repeat(3, minmax(0, 1fr)); gap: 8px 14px; margin: 0; padding: 0; list-style: none; font-size: 13px;color: #555; }
        .customer-password-rules-list li::before { content: '✓'; display: inline-flex; align-items: center; justify-content: center; width: 17px; height: 17px; border-radius: 50%; background: #219653; color: #fff; font-size: 11px; margin-inline-end: 7px; }
        .customer-register-terms { grid-column: 1 / -1; border-top: 1px solid #eeeeee; padding-top: 16px; color: #555; font-size: 14px; }
        .customer-register-actions { grid-column: 1 / -1; display: grid; grid-template-columns: 1fr auto; gap: 18px; align-items: center; }
        .customer-register-submit { min-width: 230px; }
        .customer-register-login-link { color: #b98619; font-weight: 700; text-decoration: underline; }
        .customer-auth-note { border: 1px solid #d9eadf; background: #f2fbf5; color: #1f6b3a; border-radius: 8px; padding: 12px 14px; margin-bottom: 18px; line-height: 1.7; }
        .customer-auth-divider { border: 0; border-top: 1px solid #eeeeee; margin: 24px 0; }
        .customer-auth-section-title { font-size: 15px; font-weight: 700; margin: 0 0 12px; }
        .customer-auth-help { color: #666; line-height: 1.7; margin-bottom: 16px; }
        @media (max-width: 767.98px) {
            .customer-register-modal .modal-dialog { max-width: calc(100% - 16px); margin: 8px auto; }
            .customer-register-form { max-height: 82vh; }
            .customer-register-grid { grid-template-columns: 1fr; gap: 14px; }
            .customer-password-rules-list { grid-template-columns: 1fr; }
            .customer-register-actions { grid-template-columns: 1fr; }
            .customer-register-submit { width: 100%; min-width: 0; }
        }
    </style>
    {{-- NOTE 22 AUTH R1: inline validation styles --}}
    <style>
        #login .tf-field.has-error .tf-field-label,
        #activateAccount .tf-field.has-error .tf-field-label,
        #forgotPassword .tf-field.has-error .tf-field-label {
            color: #b42318;
        }

        #login .tf-field-input.is-invalid,
        #activateAccount .tf-field-input.is-invalid,
        #forgotPassword .tf-field-input.is-invalid {
            border-color: #d92d20 !important;
            box-shadow: 0 0 0 3px rgba(217, 45, 32, 0.10);
        }

        #login .tf-field-input.is-invalid:focus,
        #activateAccount .tf-field-input.is-invalid:focus,
        #forgotPassword .tf-field-input.is-invalid:focus {
            border-color: #b42318 !important;
            box-shadow: 0 0 0 4px rgba(217, 45, 32, 0.14);
        }

        #login .customer-auth-inline-error,
        #activateAccount .customer-auth-inline-error,
        #forgotPassword .customer-auth-inline-error {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-top: 7px;
            color: #b42318;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.5;
        }

        #login .customer-auth-inline-error::before,
        #activateAccount .customer-auth-inline-error::before,
        #forgotPassword .customer-auth-inline-error::before {
            content: '!';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 16px;
            width: 16px;
            height: 16px;
            margin-top: 1px;
            border: 1px solid currentColor;
            border-radius: 50%;
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
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
                            <label class="tf-field-label">{{ __('customer_auth.login_identifier') }}</label>
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
                            <a href="#forgotPassword" data-bs-toggle="modal" class="btn-link link">{{ __('customer_auth.forgot_password') }}</a>
                            <a href="#activateAccount" data-bs-toggle="modal" class="btn-link link">{{ __('customer_auth.activate_existing') }}</a>
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

    {{-- NOTE 22 R5: custom registration validation styles --}}
    <style>
        #register .customer-register-field.has-error .customer-register-label {
            color: #b42318;
        }

        #register .customer-register-control.is-invalid {
            border-color: #d92d20 !important;
            box-shadow: 0 0 0 3px rgba(217, 45, 32, 0.10);
        }

        #register .customer-register-control.is-invalid:focus {
            border-color: #b42318 !important;
            box-shadow: 0 0 0 4px rgba(217, 45, 32, 0.14);
        }

        #register .customer-register-error {
            display: flex;
            align-items: flex-start;
            gap: 6px;
            margin-top: 7px;
            color: #b42318;
            font-size: 12px;
            font-weight: 500;
            line-height: 1.5;
        }

        #register .customer-register-error::before {
            content: '!';
            display: inline-flex;
            align-items: center;
            justify-content: center;
            flex: 0 0 16px;
            width: 16px;
            height: 16px;
            margin-top: 1px;
            border: 1px solid currentColor;
            border-radius: 50%;
            font-size: 10px;
            font-weight: 700;
            line-height: 1;
        }

        #register .customer-register-required {
            display: inline-block;
            margin-inline-start: 3px;
            color: #d92d20;
        }
    </style>
    <div class="modal modalCentered fade form-sign-in modal-part-content customer-register-modal" id="register" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="header">
                    <div class="demo-title">{{ __('front.auth.register_title') }}</div>
                    <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
                </div>
                <div class="tf-login-form">
                    <form
                            id="customer-register-form"
                            method="POST"
                            action="{{ route('front.customer.register') }}"
                            novalidate
                            data-validation-required="{{ __('front.auth.validation_required') }}"
                            data-validation-select-required="{{ __('front.auth.validation_select_required') }}"
                            data-validation-invalid="{{ __('front.auth.validation_invalid') }}"
                            data-validation-name="{{ __('front.auth.full_name_three_parts') }}"
                            data-validation-email="{{ __('front.auth.validation_email_invalid') }}"
                            data-validation-mobile="{{ __('front.auth.validation_mobile_invalid') }}"
                            data-validation-password-min="{{ __('front.auth.validation_password_min') }}"
                            data-validation-password-mismatch="{{ __('front.auth.validation_password_mismatch') }}"
                        >
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
                            $selectedNationality = old('nationality_choice', old('nationality'));
                            $otherNationality = $selectedNationality === 'other' ? old('nationality') : '';
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
                                <select
                                    id="register_nationality"
                                    class="customer-register-control"
                                    name="nationality_choice"
                                    required
                                    onchange="
                                        const field = document.getElementById('register_other_nationality_field');
                                        const input = document.getElementById('register_other_nationality');
                                        const isOther = this.value === 'other';
                                        field.hidden = !isOther;
                                        input.disabled = !isOther;
                                        input.required = isOther;
                                        if (! isOther) input.value = '';
                                    "
                                >
                                    <option value="">{{ __('front.auth.select_nationality') }}</option>
                                    @foreach ($nationalityOptions as $nationality)
                                        <option value="{{ $nationality }}" @selected($selectedNationality === $nationality)>{{ __('front.auth.nationalities.' . $nationality) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <div
                                id="register_other_nationality_field"
                                class="customer-register-field"
                                @if ($selectedNationality !== 'other') hidden @endif
                            >
                                <label class="customer-register-label" for="register_other_nationality">{{ __('front.auth.other_nationality') }}<span class="customer-register-required">*</span></label>
                                <input
                                    id="register_other_nationality"
                                    class="customer-register-control"
                                    type="text"
                                    name="nationality"
                                    value="{{ $otherNationality }}"
                                    placeholder="{{ __('front.auth.other_nationality_placeholder') }}"
                                    maxlength="255"
                                    autocomplete="country-name"
                                    @disabled($selectedNationality !== 'other')
                                    @required($selectedNationality === 'other')
                                >
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
                                <label class="customer-register-label" for="register_email">{{ __('customer_auth.email') }}<span class="customer-register-required">*</span></label>
                                <input id="register_email" class="customer-register-control" type="email" name="email" value="{{ old('email') }}" placeholder="{{ __('front.auth.email_placeholder') }}" autocomplete="email" dir="ltr" required>
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

    {{-- NOTE 22 R5: custom registration validation behavior --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const form = document.getElementById('customer-register-form');

            if (! form || form.dataset.note22ValidationReady === '1') {
                return;
            }

            form.dataset.note22ValidationReady = '1';

            const nationalitySelect = document.getElementById('register_nationality');
            const otherNationalityField = document.getElementById('register_other_nationality_field');
            const otherNationalityInput = document.getElementById('register_other_nationality');
            const passwordInput = document.getElementById('register_password');
            const passwordConfirmationInput = document.getElementById('register_password_confirmation');
            const controls = Array.from(form.querySelectorAll('.customer-register-control'));
            const serverErrors = @json($errors->customerRegister->toArray());
            let submittedOnce = Object.keys(serverErrors || {}).length > 0;

            const isVisible = function (control) {
                return ! control.disabled && ! control.hidden && control.offsetParent !== null;
            };

            const clearError = function (control) {
                const wrapper = control.closest('.customer-register-field');
                const errorId = control.id ? control.id + '_error' : '';

                control.classList.remove('is-invalid');
                control.removeAttribute('aria-invalid');

                if (errorId && control.getAttribute('aria-describedby') === errorId) {
                    control.removeAttribute('aria-describedby');
                }

                if (wrapper) {
                    wrapper.classList.remove('has-error');
                    const error = wrapper.querySelector('.customer-register-error[data-control-id="' + control.id + '"]');
                    if (error) {
                        error.remove();
                    }
                }
            };

            const showError = function (control, message) {
                const wrapper = control.closest('.customer-register-field');

                if (! wrapper || ! message) {
                    return;
                }

                clearError(control);

                const error = document.createElement('div');
                error.className = 'customer-register-error';
                error.dataset.controlId = control.id;
                error.id = control.id + '_error';
                error.setAttribute('role', 'alert');
                error.textContent = message;

                wrapper.classList.add('has-error');
                control.classList.add('is-invalid');
                control.setAttribute('aria-invalid', 'true');
                control.setAttribute('aria-describedby', error.id);
                wrapper.appendChild(error);
            };

            const validationMessage = function (control) {
                if (! isVisible(control)) {
                    return '';
                }

                const value = String(control.value || '').trim();

                if (control.required && value === '') {
                    return control.tagName === 'SELECT'
                        ? form.dataset.validationSelectRequired
                        : form.dataset.validationRequired;
                }

                if (control.name === 'name' && value !== '' && ! /^\S+\s+\S+\s+\S+/u.test(value)) {
                    return form.dataset.validationName;
                }

                if ((control.name === 'mobile' || control.name === 'secondary_mobile') && value !== '' && ! /^9[0-9]{8}$/.test(value)) {
                    return form.dataset.validationMobile;
                }

                if (control.type === 'email' && value !== '' && ! control.validity.valid) {
                    return form.dataset.validationEmail;
                }

                if (control.name === 'password' && value !== '' && value.length < 8) {
                    return form.dataset.validationPasswordMin;
                }

                if (control.name === 'password_confirmation' && value !== '' && passwordInput && value !== passwordInput.value) {
                    return form.dataset.validationPasswordMismatch;
                }

                if (! control.validity.valid) {
                    return form.dataset.validationInvalid;
                }

                return '';
            };

            const validateControl = function (control) {
                const message = validationMessage(control);

                if (message) {
                    showError(control, message);
                    return false;
                }

                clearError(control);
                return true;
            };

            const syncOtherNationality = function () {
                if (! nationalitySelect || ! otherNationalityField || ! otherNationalityInput) {
                    return;
                }

                const isOther = nationalitySelect.value === 'other';
                otherNationalityField.hidden = ! isOther;
                otherNationalityInput.disabled = ! isOther;
                otherNationalityInput.required = isOther;

                if (! isOther) {
                    otherNationalityInput.value = '';
                    clearError(otherNationalityInput);
                }
            };

            if (nationalitySelect) {
                nationalitySelect.addEventListener('change', function () {
                    syncOtherNationality();
                    if (submittedOnce) {
                        validateControl(nationalitySelect);
                        if (otherNationalityInput && ! otherNationalityInput.disabled) {
                            validateControl(otherNationalityInput);
                        }
                    }
                });
            }

            controls.forEach(function (control) {
                const eventName = control.tagName === 'SELECT' || control.type === 'date' ? 'change' : 'input';

                control.addEventListener(eventName, function () {
                    if (submittedOnce || control.classList.contains('is-invalid')) {
                        validateControl(control);
                    }

                    if (control === passwordInput && passwordConfirmationInput && passwordConfirmationInput.value !== '') {
                        validateControl(passwordConfirmationInput);
                    }
                });

                control.addEventListener('blur', function () {
                    if (submittedOnce) {
                        validateControl(control);
                    }
                });
            });

            form.addEventListener('submit', function (event) {
                submittedOnce = true;
                syncOtherNationality();

                let firstInvalid = null;

                controls.forEach(function (control) {
                    if (! validateControl(control) && ! firstInvalid) {
                        firstInvalid = control;
                    }
                });

                if (! firstInvalid) {
                    return;
                }

                event.preventDefault();
                firstInvalid.focus({ preventScroll: true });
                firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
            });

            syncOtherNationality();

            Object.entries(serverErrors || {}).forEach(function ([name, messages]) {
                const control = form.elements.namedItem(name);
                if (! control || ! control.id || ! isVisible(control)) {
                    return;
                }

                const message = Array.isArray(messages) ? messages[0] : String(messages || '');
                showError(control, message);
            });
        });
    </script>
    <div class="modal modalCentered fade form-sign-in modal-part-content" id="activateAccount" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="header">
                    <div class="demo-title">{{ __('customer_auth.activation_title') }}</div>
                    <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
                </div>
                <div class="tf-login-form">
                    <p class="customer-auth-help">{{ __('customer_auth.activation_help') }}</p>

                    @if (session('auth_notice') && session('auth_modal') === 'activateAccount')
                        <div class="customer-auth-note">{{ session('auth_notice') }}</div>
                    @endif

                    @if ($errors->customerActivationCode->any())
                        <div class="alert alert-danger mb_20">
                            @foreach ($errors->customerActivationCode->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('front.customer.activate.code') }}">
                        @csrf

                        <div class="customer-auth-section-title">{{ __('customer_auth.activation_request_help') }}</div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="email" name="email" value="{{ old('email', session('activation_email')) }}" autocomplete="email" dir="ltr" required>
                            <label class="tf-field-label">{{ __('customer_auth.email') }}</label>
                        </div>
                        <button type="submit" class="tf-btn btn-outline radius-3 w-100 justify-content-center">
                            {{ __('customer_auth.send_activation_code') }}
                        </button>
                    </form>

                    <hr class="customer-auth-divider">

                    @if ($errors->customerActivate->any())
                        <div class="alert alert-danger mb_20">
                            @foreach ($errors->customerActivate->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('front.customer.activate') }}">
                        @csrf

                        <p class="customer-auth-help">{{ __('customer_auth.activation_complete_help') }}</p>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="email" name="email" value="{{ old('email', session('activation_email')) }}" autocomplete="email" dir="ltr" required>
                            <label class="tf-field-label">{{ __('customer_auth.email') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="text" name="code" value="{{ old('code') }}" inputmode="numeric" autocomplete="one-time-code" maxlength="6" dir="ltr" required>
                            <label class="tf-field-label">{{ __('customer_auth.verification_code') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password" autocomplete="new-password" required>
                            <label class="tf-field-label">{{ __('customer_auth.new_password') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password_confirmation" autocomplete="new-password" required>
                            <label class="tf-field-label">{{ __('customer_auth.password_confirmation') }}</label>
                        </div>
                        <div class="bottom">
                            <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                                {{ __('customer_auth.activate_button') }}
                            </button>
                            <a href="#login" data-bs-toggle="modal" class="btn-link fw-6 w-100 link">{{ __('customer_auth.back_to_login') }}</a>
                        </div>
                    </form>

                    <small class="text-muted d-block mt_16">{{ __('customer_auth.resend_note') }}</small>
                </div>
            </div>
        </div>
    </div>

    <div class="modal modalCentered fade form-sign-in modal-part-content" id="forgotPassword" tabindex="-1" aria-hidden="true">
        <div class="modal-dialog modal-dialog-centered">
            <div class="modal-content">
                <div class="header">
                    <div class="demo-title">{{ __('customer_auth.password_reset_title') }}</div>
                    <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
                </div>
                <div class="tf-login-form">
                    <p class="customer-auth-help">{{ __('customer_auth.password_reset_help') }}</p>

                    @if (session('auth_notice') && session('auth_modal') === 'forgotPassword')
                        <div class="customer-auth-note">{{ session('auth_notice') }}</div>
                    @endif

                    @if ($errors->customerPasswordResetCode->any())
                        <div class="alert alert-danger mb_20">
                            @foreach ($errors->customerPasswordResetCode->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('front.customer.forgot-password.code') }}">
                        @csrf

                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="email" name="email" value="{{ old('email', session('password_reset_email')) }}" autocomplete="email" dir="ltr" required>
                            <label class="tf-field-label">{{ __('customer_auth.email') }}</label>
                        </div>
                        <button type="submit" class="tf-btn btn-outline radius-3 w-100 justify-content-center">
                            {{ __('customer_auth.send_password_reset_code') }}
                        </button>
                    </form>

                    <hr class="customer-auth-divider">

                    @if ($errors->customerForgotPassword->any())
                        <div class="alert alert-danger mb_20">
                            @foreach ($errors->customerForgotPassword->all() as $error)
                                <div>{{ $error }}</div>
                            @endforeach
                        </div>
                    @endif

                    <form method="POST" action="{{ route('front.customer.forgot-password') }}">
                        @csrf

                        <p class="customer-auth-help">{{ __('customer_auth.password_reset_complete_help') }}</p>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="email" name="email" value="{{ old('email', session('password_reset_email')) }}" autocomplete="email" dir="ltr" required>
                            <label class="tf-field-label">{{ __('customer_auth.email') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="text" name="code" value="{{ old('code') }}" inputmode="numeric" autocomplete="one-time-code" maxlength="6" dir="ltr" required>
                            <label class="tf-field-label">{{ __('customer_auth.verification_code') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password" autocomplete="new-password" required>
                            <label class="tf-field-label">{{ __('customer_auth.new_password') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password_confirmation" autocomplete="new-password" required>
                            <label class="tf-field-label">{{ __('customer_auth.password_confirmation') }}</label>
                        </div>
                        <div class="bottom">
                            <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                                {{ __('customer_auth.reset_password_button') }}
                            </button>
                            <a href="#login" data-bs-toggle="modal" class="btn-link fw-6 w-100 link">{{ __('customer_auth.back_to_login') }}</a>
                        </div>
                    </form>

                    <small class="text-muted d-block mt_16">{{ __('customer_auth.resend_note') }}</small>
                </div>
            </div>
        </div>
    </div>

    {{-- NOTE 22 AUTH R1: inline validation behavior --}}
    <script>
        document.addEventListener('DOMContentLoaded', function () {
            const messages = {
                required: @json(__('front.auth.validation_required')),
                invalid: @json(__('front.auth.validation_invalid')),
                email: @json(__('front.auth.validation_email_invalid')),
                code: @json(__('front.auth.validation_code')),
                passwordMin: @json(__('front.auth.validation_password_min')),
                passwordMismatch: @json(__('front.auth.validation_password_mismatch')),
            };

            const groups = [
                { modalId: 'login', formIndex: 0, errors: @json($errors->customerLogin->toArray()) },
                { modalId: 'activateAccount', formIndex: 0, errors: @json($errors->customerActivationCode->toArray()) },
                { modalId: 'activateAccount', formIndex: 1, errors: @json($errors->customerActivate->toArray()) },
                { modalId: 'forgotPassword', formIndex: 0, errors: @json($errors->customerPasswordResetCode->toArray()) },
                { modalId: 'forgotPassword', formIndex: 1, errors: @json($errors->customerForgotPassword->toArray()) },
            ];

            const isUsable = function (control) {
                return ! control.disabled
                    && control.type !== 'hidden'
                    && ! control.hidden
                    && ! control.closest('[hidden]');
            };

            const setupForm = function (group) {
                const modal = document.getElementById(group.modalId);
                if (! modal) {
                    return;
                }

                const forms = modal.querySelectorAll('form');
                const form = forms[group.formIndex];
                if (! form || form.dataset.note22AuthValidationReady === '1') {
                    return;
                }

                form.dataset.note22AuthValidationReady = '1';
                form.noValidate = true;

                const controls = Array.from(form.querySelectorAll('input, select, textarea'))
                    .filter(isUsable);
                let submittedOnce = Object.keys(group.errors || {}).length > 0;

                controls.forEach(function (control, index) {
                    if (! control.id) {
                        const safeName = String(control.name || 'field').replace(/[^a-zA-Z0-9_-]/g, '-');
                        control.id = 'auth-' + group.modalId + '-' + group.formIndex + '-' + safeName + '-' + index;
                    }
                });

                const clearError = function (control) {
                    const wrapper = control.closest('.tf-field');
                    const errorId = control.id + '_error';

                    control.classList.remove('is-invalid');
                    control.removeAttribute('aria-invalid');

                    if (control.getAttribute('aria-describedby') === errorId) {
                        control.removeAttribute('aria-describedby');
                    }

                    if (wrapper) {
                        wrapper.classList.remove('has-error');
                        const error = wrapper.querySelector('.customer-auth-inline-error[data-control-id="' + control.id + '"]');
                        if (error) {
                            error.remove();
                        }
                    }
                };

                const showError = function (control, message) {
                    const wrapper = control.closest('.tf-field');
                    if (! wrapper || ! message) {
                        return;
                    }

                    clearError(control);

                    const error = document.createElement('div');
                    error.className = 'customer-auth-inline-error';
                    error.dataset.controlId = control.id;
                    error.id = control.id + '_error';
                    error.setAttribute('role', 'alert');
                    error.textContent = message;

                    wrapper.classList.add('has-error');
                    control.classList.add('is-invalid');
                    control.setAttribute('aria-invalid', 'true');
                    control.setAttribute('aria-describedby', error.id);
                    wrapper.appendChild(error);
                };

                const validationMessage = function (control) {
                    if (! isUsable(control)) {
                        return '';
                    }

                    const value = String(control.value || '').trim();

                    if (control.required && value === '') {
                        return messages.required;
                    }

                    if (control.type === 'email' && value !== '' && ! control.validity.valid) {
                        return messages.email;
                    }

                    if (control.name === 'code' && value !== '' && ! /^[0-9]{6}$/.test(value)) {
                        return messages.code;
                    }

                    if (control.name === 'password' && control.autocomplete === 'new-password' && value !== '' && value.length < 8) {
                        return messages.passwordMin;
                    }

                    if (control.name === 'password_confirmation' && value !== '') {
                        const password = form.querySelector('[name="password"]');
                        if (password && value !== password.value) {
                            return messages.passwordMismatch;
                        }
                    }

                    if (! control.validity.valid) {
                        return messages.invalid;
                    }

                    return '';
                };

                const validateControl = function (control) {
                    const message = validationMessage(control);

                    if (message) {
                        showError(control, message);
                        return false;
                    }

                    clearError(control);
                    return true;
                };

                controls.forEach(function (control) {
                    const eventName = control.tagName === 'SELECT' ? 'change' : 'input';

                    control.addEventListener(eventName, function () {
                        if (submittedOnce || control.classList.contains('is-invalid')) {
                            validateControl(control);
                        }

                        if (control.name === 'password') {
                            const confirmation = form.querySelector('[name="password_confirmation"]');
                            if (confirmation && confirmation.value !== '') {
                                validateControl(confirmation);
                            }
                        }
                    });

                    control.addEventListener('blur', function () {
                        if (submittedOnce) {
                            validateControl(control);
                        }
                    });
                });

                form.addEventListener('submit', function (event) {
                    submittedOnce = true;
                    let firstInvalid = null;

                    controls.forEach(function (control) {
                        if (! validateControl(control) && ! firstInvalid) {
                            firstInvalid = control;
                        }
                    });

                    if (! firstInvalid) {
                        return;
                    }

                    event.preventDefault();
                    firstInvalid.focus({ preventScroll: true });
                    firstInvalid.scrollIntoView({ behavior: 'smooth', block: 'center' });
                });

                Object.entries(group.errors || {}).forEach(function ([name, errorMessages]) {
                    const control = form.elements.namedItem(name);
                    if (! control || ! control.id || ! isUsable(control)) {
                        return;
                    }

                    const message = Array.isArray(errorMessages)
                        ? errorMessages[0]
                        : String(errorMessages || '');
                    showError(control, message);
                });
            };

            groups.forEach(setupForm);
        });
    </script>
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
