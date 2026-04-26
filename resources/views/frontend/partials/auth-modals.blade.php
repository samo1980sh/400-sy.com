<div class="modal modalCentered fade form-sign-in modal-part-content" id="login">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="header">
                <div class="demo-title">Log in</div>
                <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
            </div>
            <div class="tf-login-form">
                <form action="#" accept-charset="utf-8">
                    <div class="tf-field style-1">
                        <input class="tf-field-input tf-input" placeholder=" " type="tel" name="mobile_number" inputmode="numeric" pattern="[1-9][0-9]{7,14}">
                        <label class="tf-field-label">Mobile Number</label>
                    </div>
                    <div class="mb_10 text-center text_black-2">OR</div>
                    <div class="tf-field style-1">
                        <input class="tf-field-input tf-input" placeholder=" " type="text" name="card_number" inputmode="numeric">
                        <label class="tf-field-label">Card Number</label>
                    </div>
                    <div class="tf-field style-1">
                        <input class="tf-field-input tf-input" placeholder=" " type="password" name="password">
                        <label class="tf-field-label">Password *</label>
                    </div>
                    <div>
                        <a href="#forgotPassword" data-bs-toggle="modal" class="btn-link link">Forgot your password?</a>
                    </div>
                    <div class="bottom">
                        <div class="w-100">
                            <button type="submit" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center"><span>Log in</span></button>
                        </div>
                        <div class="w-100">
                            <a href="#register" data-bs-toggle="modal" class="btn-link fw-6 w-100 link">
                                New customer? Create your account
                                <i class="icon icon-arrow1-top-left"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal modalCentered fade form-sign-in modal-part-content" id="forgotPassword">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="header">
                <div class="demo-title">Reset your password</div>
                <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
            </div>
            <div class="tf-login-form">
                <form class="">
                    <div>
                        <p>Sign up for early Sale access plus tailored new arrivals, trends and promotions. To opt out, click unsubscribe in our emails</p>
                    </div>
                    <div class="tf-field style-1">
                        <input class="tf-field-input tf-input" placeholder=" " type="email" name="">
                        <label class="tf-field-label">Email *</label>
                    </div>
                    <div>
                        <a href="#login" data-bs-toggle="modal" class="btn-link link">Cancel</a>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>

<div class="modal modalCentered fade form-sign-in modal-part-content" id="register">
    <div class="modal-dialog modal-dialog-centered">
        <div class="modal-content">
            <div class="header">
                <div class="demo-title">Register</div>
                <span class="icon-close icon-close-popup" data-bs-dismiss="modal"></span>
            </div>
            <div class="tf-login-form">
                <form class="">
                    <div class="tf-field style-1">
                        <input class="tf-field-input tf-input" placeholder=" " type="text" name="name">
                        <label class="tf-field-label">First name</label>
                    </div>
                    <div class="tf-field style-1">
                        <input class="tf-field-input tf-input" placeholder=" " type="text" name="name">
                        <label class="tf-field-label">Last name</label>
                    </div>
                    <div class="tf-field style-1">
                        <input class="tf-field-input tf-input" placeholder=" " type="email" name="">
                        <label class="tf-field-label">Email *</label>
                    </div>
                    <div class="tf-field style-1">
                        <input class="tf-field-input tf-input" placeholder=" " type="password" name="">
                        <label class="tf-field-label">Password *</label>
                    </div>
                    <div class="bottom">
                        <div class="w-100">
                            <a href="{{ asset('static-template/register.html') }}" class="tf-btn btn-fill animate-hover-btn radius-3 w-100 justify-content-center"><span>Register</span></a>
                        </div>
                        <div class="w-100">
                            <a href="#login" data-bs-toggle="modal" class="btn-link fw-6 w-100 link">
                                Already have an account? Log in here
                                <i class="icon icon-arrow1-top-left"></i>
                            </a>
                        </div>
                    </div>
                </form>
            </div>
        </div>
    </div>
</div>
