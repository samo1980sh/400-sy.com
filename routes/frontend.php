<?php

use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\FrontCustomerAccountController;
use App\Http\Controllers\FrontCustomerAuthController;
use App\Http\Controllers\FrontPageController;
use App\Http\Controllers\FrontGiftCardRequestController;
use App\Http\Middleware\AuthenticateFrontCustomer;
use Illuminate\Support\Facades\Route;

Route::middleware('front.locale')->group(function (): void {
    Route::get('/', [FrontPageController::class, 'home'])->name('front.home');
    Route::get('/category/{slug}', [FrontPageController::class, 'category'])->name('front.category');
    Route::get('/lang/{locale}', [FrontPageController::class, 'setLocale'])->name('front.locale');
    Route::post('/currency', [CurrencyController::class, 'update'])->name('front.currency');

    Route::post('/account/login', [FrontCustomerAuthController::class, 'login'])
        ->middleware('throttle:6,1')
        ->name('front.customer.login');
    Route::post('/account/register', [FrontCustomerAuthController::class, 'register'])
        ->middleware('throttle:4,1')
        ->name('front.customer.register');
    Route::post('/account/activate', [FrontCustomerAuthController::class, 'activate'])
        ->middleware('throttle:4,1')
        ->name('front.customer.activate');
    Route::post('/account/logout', [FrontCustomerAuthController::class, 'logout'])
        ->name('front.customer.logout');

    Route::middleware(AuthenticateFrontCustomer::class)
        ->prefix('account')
        ->name('front.account.')
        ->group(function (): void {
            Route::get('/', [FrontCustomerAccountController::class, 'index'])->name('index');
            Route::get('/profile', [FrontCustomerAccountController::class, 'profile'])->name('profile');
            Route::patch('/profile', [FrontCustomerAccountController::class, 'updateProfile'])->name('profile.update');
            Route::patch('/password', [FrontCustomerAccountController::class, 'updatePassword'])->name('password.update');

            Route::get('/addresses', [FrontCustomerAccountController::class, 'addresses'])->name('addresses');
            Route::post('/addresses', [FrontCustomerAccountController::class, 'storeAddress'])->name('addresses.store');
            Route::patch('/addresses/{address}', [FrontCustomerAccountController::class, 'updateAddress'])->name('addresses.update');
            Route::patch('/addresses/{address}/default', [FrontCustomerAccountController::class, 'setDefaultAddress'])->name('addresses.default');
            Route::delete('/addresses/{address}', [FrontCustomerAccountController::class, 'destroyAddress'])->name('addresses.destroy');

            Route::get('/orders', [FrontCustomerAccountController::class, 'orders'])->name('orders');
            Route::get('/orders/{order:order_no}', [FrontCustomerAccountController::class, 'showOrder'])->name('orders.show');
            Route::get('/gift-card-requests', [FrontGiftCardRequestController::class, 'accountIndex'])->name('gift-card-requests.index');
            Route::get('/gift-card-requests/{giftCardRequest:request_no}', [FrontGiftCardRequestController::class, 'accountShow'])->name('gift-card-requests.show');
        });

    Route::get('/products', [FrontPageController::class, 'productsIndex'])->name('front.products.index');
    Route::get('/offers', [FrontPageController::class, 'offers'])->name('front.offers');
    Route::get('/gift-cards', [FrontGiftCardRequestController::class, 'create'])->name('front.gift-cards.create');
    Route::post('/gift-cards', [FrontGiftCardRequestController::class, 'store'])
        ->middleware(AuthenticateFrontCustomer::class)
        ->name('front.gift-cards.store');
    Route::get('/wishlist', [FrontPageController::class, 'wishlist'])->name('front.wishlist.index');
    Route::post('/wishlist/items/{product:slug}', [FrontPageController::class, 'addToWishlist'])->name('front.wishlist.add');
    Route::delete('/wishlist/items/{product:slug}', [FrontPageController::class, 'removeFromWishlist'])->name('front.wishlist.remove');
    Route::get('/products/{slug}', [FrontPageController::class, 'product'])->name('front.products.show');
    Route::get('/products/{product:slug}/quick-view', [FrontPageController::class, 'quickView'])->name('front.products.quick-view');
    Route::get('/cart', [FrontPageController::class, 'cart'])->name('front.cart.view');
    Route::get('/checkout', [FrontPageController::class, 'checkout'])->name('front.checkout');
    Route::post('/checkout/coupon/preview', [FrontPageController::class, 'previewCheckoutCoupon'])
        ->middleware('throttle:10,1')
        ->name('front.checkout.coupon.preview');
    Route::post('/checkout', [FrontPageController::class, 'storeCheckout'])->name('front.checkout.store');
    Route::get('/checkout/success/{order:order_no}', [FrontPageController::class, 'checkoutSuccess'])->name('front.checkout.success');
    Route::post('/pages/contact-us', [FrontPageController::class, 'sendContactMessage'])
        ->middleware('throttle:3,10')
        ->name('front.contact.send');
    Route::get('/pages/{slug}', [FrontPageController::class, 'page'])->name('front.pages.show');
    Route::post('/cart/items/{product:slug}', [FrontPageController::class, 'addToCart'])->name('front.cart.add');
    Route::patch('/cart/items/{key}', [FrontPageController::class, 'updateCartItem'])->name('front.cart.update');
    Route::delete('/cart/items/{key}', [FrontPageController::class, 'removeCartItem'])->name('front.cart.remove');
});

