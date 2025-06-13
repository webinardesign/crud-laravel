<?php
use App\Http\Controllers\ProductController;
use Illuminate\Support\Facades\Route;

Route::get('/', function () {
    return view('welcome');
});

Route::controller(ProductController::class)->group(function(){
    Route::get('/products','index')->name('products.index');
    Route::get('/products/create','create')->name('products.create');
    Route::post('/products','store')->name('products.store');

    Route::get('/products/{product}/edit','edit')->name('products.edit');
    Route::put('/products/{product}','update')->name('products.update');
       Route::delete('/products/{product}','destroy')->name('products.destroy');  
    Route::get('/products/{product}/productDetails','productDetails')->name('products.productDetails');  
    Route::post('products/{id}/add-to-cart', [ProductController::class, 'addToCart'])->name('products.addToCart');
    Route::get('/products/cart', [ProductController::class, 'viewCart'])->name('products.cart');
    Route::get('cart/remove/{id}', [ProductController::class, 'removeFromCart'])->name('cart.remove');
    Route::get('checkout/{id}', [ProductController::class, 'checkoutForm'])->name('products.checkout');
Route::post('checkout/store', [ProductController::class, 'storeBilling'])->name('billing.store');
Route::get('/order-success', function () {
    return view('products.success');
})->name('products.success');

});