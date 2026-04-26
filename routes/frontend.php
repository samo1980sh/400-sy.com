<?php

use App\Http\Controllers\CurrencyController;
use App\Http\Controllers\FrontPageController;
use Illuminate\Support\Facades\Route;

Route::middleware('front.locale')->group(function (): void {
    Route::get('/', [FrontPageController::class, 'home'])->name('front.home');
    Route::get('/category/{slug}', [FrontPageController::class, 'category'])->name('front.category');
    Route::get('/lang/{locale}', [FrontPageController::class, 'setLocale'])->name('front.locale');
    Route::post('/currency', [CurrencyController::class, 'update'])->name('front.currency');
    Route::get('/products', [FrontPageController::class, 'productsIndex'])->name('front.products.index');
    Route::get('/products/{slug}', [FrontPageController::class, 'product'])->name('front.products.show');
    Route::get('/products/{product:slug}/quick-view', [FrontPageController::class, 'quickView'])->name('front.products.quick-view');
    Route::get('/cart', [FrontPageController::class, 'cart'])->name('front.cart.view');
    Route::get('/checkout', [FrontPageController::class, 'checkout'])->name('front.checkout');
    Route::get('/pages/{slug}', [FrontPageController::class, 'page'])->name('front.pages.show');
    Route::post('/cart/items/{product:slug}', [FrontPageController::class, 'addToCart'])->name('front.cart.add');
    Route::patch('/cart/items/{key}', [FrontPageController::class, 'updateCartItem'])->name('front.cart.update');
    Route::delete('/cart/items/{key}', [FrontPageController::class, 'removeCartItem'])->name('front.cart.remove');
});
