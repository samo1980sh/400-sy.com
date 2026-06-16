@guest('customer')
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

    <div class="modal modalCentered fade form-sign-in modal-part-content" id="register" tabindex="-1" aria-hidden="true">
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

                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="text" name="name" value="{{ old('name') }}" autocomplete="name" required>
                            <label class="tf-field-label">{{ __('front.auth.full_name') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="tel" name="mobile" value="{{ old('mobile') }}" autocomplete="tel" dir="ltr" required>
                            <label class="tf-field-label">{{ __('front.auth.mobile_number') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="email" name="email" value="{{ old('email') }}" autocomplete="email" dir="ltr">
                            <label class="tf-field-label">{{ __('front.auth.email_optional') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password" autocomplete="new-password" required>
                            <label class="tf-field-label">{{ __('front.auth.password_plain') }}</label>
                        </div>
                        <div class="tf-field style-1">
                            <input class="tf-field-input tf-input" placeholder=" " type="password" name="password_confirmation" autocomplete="new-password" required>
                            <label class="tf-field-label">{{ __('front.auth.password_confirmation') }}</label>
                        </div>
                        <div class="bottom">
                            <div class="w-100">
                                <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center">
                                    <span>{{ __('front.auth.register_button') }}</span>
                                </button>
                            </div>
                            <div class="w-100">
                                <a href="#login" data-bs-toggle="modal" class="btn-link fw-6 w-100 link">
                                    {{ __('front.auth.already_have_account') }}
                                    <i class="icon icon-arrow1-top-left"></i>
                                </a>
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
                    <p class="mb_20">{{ __('front.auth.reset_unavailable_message') }}</p>
                    <a href="#login" data-bs-toggle="modal" class="tf-btn btn-outline radius-3 w-100 justify-content-center">
                        {{ __('front.auth.back_to_login') }}
                    </a>
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
