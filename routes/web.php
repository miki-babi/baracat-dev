<?php

use App\Livewire\CheckoutPage;
use App\Livewire\CheckoutSuccessPage;
use App\Livewire\CollectionPage;
use App\Livewire\Home;
use App\Livewire\ProductPage;
use App\Livewire\SearchPage;
use Illuminate\Support\Facades\Route;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;

/*
|--------------------------------------------------------------------------
| Web Routes
|--------------------------------------------------------------------------
|
| Here is where you can register web routes for your application. These
| routes are loaded by the RouteServiceProvider within a group which
| contains the "web" middleware group. Now create something great!
|
*/

Route::get('/', Home::class);

Route::get('/collections/{slug}', CollectionPage::class)->name('collection.view');

Route::get('/products/{slug}', ProductPage::class)->name('product.view');

Route::get('search', SearchPage::class)->name('search.view');

Route::get('checkout', CheckoutPage::class)->name('checkout.view');

Route::get('checkout/success', CheckoutSuccessPage::class)->name('checkout-success.view');



use Laravel\Socialite\Facades\Socialite;

Route::get('/auth/redirect', function () {
    return Socialite::driver('google')->redirect();
});

use Lunar\Models\Customer;
use Lunar\Models\Cart;

Route::get('/auth/google/callback', function () {
    $googleUser = Socialite::driver('google')->user();

    // Create or update your local user
    $user = User::updateOrCreate(
        ['google_id' => $googleUser->id],
        [
            'name' => $googleUser->name,
            'email' => $googleUser->email,
        ]
    );


    // Find or create corresponding Lunar Customer
    $customer = Customer::firstOrCreate(
        ['email' => $user->email],
        ['first_name' => explode(" ",$user->name)[0]],
        ['last_name' => explode(" ",$user->name)[1] ?? null] // Assuming phone is available
    );

    // Associate the cart with the Lunar Customer
    // $cart = Cart::current(); // gets the current session cart
    // $cart->associate($customer);
    Auth::login($user);
    // CartSession::setCart($cart);
    dd("done");
});

Route::get('/lunar/admin', function () {
    if(!Auth::check()) {
        // return redirect()->route('login');
        return "not auth";
    }
    dd(Auth::user());
    return "hello";
})->name('lunar.admin');

Route::get('/reset', function () {
//    $user = User::where('email', 'your@email.com')->first();

    // if ($user) {
    //     $user->password = Hash::make('newpassword123'); // set new password
    //     $user->save();

    //     return "✅ Password reset for {$user->email}";
    // }
    $bycrypt = Hash::make('newpassword123');
    dd($bycrypt);

    return "❌ User not found.";
});


