<?php

use Illuminate\Support\Facades\Route;
use App\Models\Stock;
use Illuminate\Support\Facades\Storage;
use Illuminate\Http\Request;

Route::view('/', 'arcana-landing')->name('landing');
Route::get('/marketplace', [\App\Http\Controllers\MarketplaceController::class, 'index'])->name('marketplace');
Route::get('/cards/{listing}', [\App\Http\Controllers\MarketplaceController::class, 'show'])->name('marketplace.show');
Route::get('/shops/{seller}', [\App\Http\Controllers\MarketplaceController::class, 'seller'])->name('marketplace.seller');

Route::middleware([
    'auth:sanctum',
    config('jetstream.auth_session'),
    'verified',
])->group(function () {
    Route::get('/dashboard', \App\Http\Controllers\DashboardController::class)->name('dashboard');
});


Route::delete('/delete-stock/{id}', function ($id) {
    $stock = Stock::find($id);

    if ($stock) {
        // Delete Image from Storage
        Storage::disk('public')->delete($stock->image ?? '');

        // Delete Stock Record
        $stock->delete();

        return response()->json(['success' => true]);
    }

    return response()->json(['success' => false, 'message' => 'Stock not found']);
})->middleware(['auth', 'can:manage-stocks']);

Route::middleware('auth')->group(function () {
    Route::post('/cards/{listing}/report', [\App\Http\Controllers\ListingReportController::class, 'store'])->middleware('throttle:5,1')->name('listing.report');
    Route::get('/seller/profile', [\App\Http\Controllers\SellerProfileController::class, 'edit'])->name('seller.profile');
    Route::put('/seller/profile', [\App\Http\Controllers\SellerProfileController::class, 'update'])->name('seller.profile.update');
    Route::get('/seller/catalog/cards', [\App\Http\Controllers\CardCatalogController::class, 'search'])->middleware('throttle:30,1')->name('catalog.search');
    Route::get('/seller/catalog/cards/{id}', [\App\Http\Controllers\CardCatalogController::class, 'show'])->middleware('throttle:30,1');
    Route::get('/checkout/{listing}', [\App\Http\Controllers\OrderController::class, 'checkout'])->name('orders.checkout');
    Route::post('/checkout/{listing}', [\App\Http\Controllers\OrderController::class, 'store'])->name('orders.store');
    Route::get('/orders', [\App\Http\Controllers\OrderController::class, 'index'])->name('orders.index');
    Route::get('/seller/orders', [\App\Http\Controllers\OrderController::class, 'sales'])->name('orders.sales');
    Route::get('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'show'])->name('orders.show');
    Route::patch('/orders/{order}', [\App\Http\Controllers\OrderController::class, 'update'])->name('orders.update');
    Route::resource('/seller/listings', \App\Http\Controllers\ListingController::class)->except(['show', 'destroy'])->names('seller.listings');
    Route::get('/seller/apply', [\App\Http\Controllers\SellerApplicationController::class, 'show'])->name('seller.apply');
    Route::post('/seller/apply', [\App\Http\Controllers\SellerApplicationController::class, 'store'])->name('seller.apply.store');
});
Route::post('/orders/{order}/demo-payment', [\App\Http\Controllers\OrderController::class, 'simulatePayment'])->middleware(['auth', 'throttle:10,1'])->name('orders.demo-payment');


