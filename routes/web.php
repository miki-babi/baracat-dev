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
use Illuminate\Support\Facades\Artisan;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Log;



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
Route::get('/test', function () {
    dd(Auth::user());
});
Route::get('/test2', function () {
    dd(Auth::user());
});

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
use Lunar\Facades\CartSession;

// Route::get('/auth/google/callback', function (Request $request) {
//     $googleUser = Socialite::driver('google')->user();

//     // Create or update local user
//     $user = User::updateOrCreate(
//         ['google_id' => $googleUser->id],
//         [
//             'name' => $googleUser->name,
//             'email' => $googleUser->email,
//         ]
//     );

//     // Log the user in (this generates a Laravel session)
//     Auth::login($user);

//     $request->session()->regenerate();
//     // Get the current cart session
//     $cart = CartSession::current();

//     // Associate cart with logged-in user
//     $cart->associate($user);

//     // Optional: persist cart session
//     // CartSession::setCart($cart);

//     return redirect('/test');
//     // dd($cart, Auth::user());
//     // return redirect('/'); // or wherever you want
// });

// Route::get('/auth/google/callback', function (\Illuminate\Http\Request $request) {
//     $googleUser = Socialite::driver('google')->user();

//     // Create or update local user
//     $user = User::updateOrCreate(
//         ['google_id' => $googleUser->id],
//         [
//             'name' => $googleUser->name,
//             'email' => $googleUser->email,
//         ]
//     );

//     // Login and regenerate session
//     Auth::login($user);
//     $request->session()->regenerate();

//     // Ensure Lunar Customer exists for this user
//     $customer = \Lunar\Models\Customer::firstOrCreate(
//         ['id' => $user->id],
//         [
//             'title' => 'Mr.',
//             'first_name' => explode(' ', $user->name)[0] ?? '',
//             'last_name' => explode(' ', $user->name)[1] ?? ' ',
//         ]
//     );

//     // Attach current cart to this user
//     $cart = \Lunar\Facades\CartSession::current();
//     $cart->associate($user);
//     // \Lunar\Facades\CartSession::setCart($cart);

//     return redirect('/test'); // or your homepage
// });


// Route::get('/auth/google/callback', function (Request $request) {
//     Log::info("🔵 Step 1: Starting Google callback");

//     // 1️⃣ Google user
//     $googleUser = Socialite::driver('google')->user();
//     Log::info("Google user", ['type' => gettype($googleUser), 'class' => get_class($googleUser)]);

//     // 2️⃣ Local user
//     $user = User::updateOrCreate(
//         ['google_id' => $googleUser->id],
//         [
//             'name' => $googleUser->name,
//             'email' => $googleUser->email,
//         ]
//     );
//     Log::info("Local User", ['id' => $user->id, 'type' => gettype($user), 'class' => get_class($user)]);

//     // 3️⃣ Login
//     Auth::login($user);
//     $request->session()->regenerate();
//     Log::info("✅ Logged in user {$user->id}");

//     // 4️⃣ Lunar customer
//     $nameParts = explode(' ', $user->name);
//     $firstName = $nameParts[0] ?? 'Not-set';
//     $lastName  = $nameParts[1] ?? 'Not-set';

//     // $customer = Customer::firstOrCreate(
//     //     ['account_ref' => $user->id],
//     //     [
//     //         'title'      => 'Mr.',
//     //         'first_name' => $firstName,
//     //         'last_name'  => $lastName,
//     //         'attribute_data'=>[[]],
//     //         'meta'       => ['email' => $user->email],
//     //     ]
//     // );
//     // Log::info("Customer created/fetched", [
//     //     'id'    => $customer->id,
//     //     'type'  => gettype($customer),
//     //     'class' => is_object($customer) ? get_class($customer) : 'NOT AN OBJECT',
//     // ]);

//     // 5️⃣ Cart
//     $cart = CartSession::current();
//     if (!$cart) {
//         Log::info("No cart found → creating new");
//         $cart = CartSession::create();
//     }
//     Log::info("Cart before attach", [
//         'id'    => $cart->id,
//         'type'  => gettype($cart),
//         'class' => get_class($cart),
//     ]);

//     // // 6️⃣ Attach customer
//     // Log::info("➡️ About to attach customer", [
//     //     'customer_type' => gettype($customer),
//     //     'customer_class'=> is_object($customer) ? get_class($customer) : 'NOT AN OBJECT'
//     // ]);
//     //     // Ensure customer is linked to current user
//     // $customer->users()->syncWithoutDetaching([$user->id]);

//     // // Ensure customer is in at least one group (id=1 is usually "Retail")
//     // if ($customer->customerGroups()->count() === 0) {
//     //     $customer->customerGroups()->sync([1]);
//     // }
//     //     CartSession::setCustomer($customer);

//     // 7️⃣ Attach user
//     $cart = CartSession::current(); // refresh
//     $cart->user()->associate($user);
//     $cart->save();
//     Log::info("✅ User associated to cart", ['cart_id' => $cart->id, 'user_id' => $user->id]);

//     return redirect('/test');
// });

Route::get('/auth/google/callback', function (Request $request) {
    Log::info("🔵 Step 1: Starting Google callback");

    // 1️⃣ Get Google user
    $googleUser = Socialite::driver('google')->user();
    Log::info("Google user", ['type' => gettype($googleUser), 'class' => get_class($googleUser)]);

    // 2️⃣ Create or update local User
    $user = User::updateOrCreate(
        ['google_id' => $googleUser->id],
        [
            'name'  => $googleUser->name,
            'email' => $googleUser->email,
        ]
    );
    Log::info("Local User", ['id' => $user->id, 'type' => gettype($user), 'class' => get_class($user)]);

    // 3️⃣ Login user and regenerate session
    Auth::login($user);
    $request->session()->regenerate();
    Log::info("✅ Logged in user {$user->id}");

    // 4️⃣ Ensure a Lunar Customer exists
    $nameParts = explode(' ', $user->name);
    $firstName = $nameParts[0] ?? '';
    $lastName  = $nameParts[1] ?? '';

// 1️⃣ Check if customer already exists
$customer = Customer::where('account_ref', $user->id)->first();

if (!$customer) {
    // 2️⃣ Create customer if not found
    $nameParts = explode(' ', $user->name);
    $firstName = $nameParts[0] ?? 'NaN';
    $lastName  = $nameParts[1] ?? 'NaN';

    $customer = Lunar\Models\Customer::create([
        'title' => 'Mr.',
        'first_name' => $firstName,
        'last_name' => $lastName,
        'company_name' => $user->name,
        'vat_no' => null,
        'meta' => [
            'account_no' => $user->id
        ],
    ]);
}

// 3️⃣ Ensure customer is linked to user
$customer->users()->syncWithoutDetaching([$user->id]);

// 4️⃣ Ensure customer belongs to at least one group
if ($customer->customerGroups()->count() === 0) {
    $customer->customerGroups()->sync([1]); // default group
}

    // 5️⃣ Ensure there is a cart session
    $cart = CartSession::current() ?? CartSession::create();
    Log::info("Cart before attach", [
        'id'    => $cart->id,
        'type'  => gettype($cart),
        'class' => get_class($cart),
    ]);

    // 6️⃣ Attach customer to cart session
    Log::info("➡️ About to attach customer", [
        'customer_type' => gettype($customer),
        'customer_class'=> is_object($customer) ? get_class($customer) : 'NOT AN OBJECT'
    ]);
    // CartSession::setCustomer($customer);

    // 7️⃣ Attach user to the same cart
    $cart = CartSession::current(); // re-fetch to ensure session cart
    $cart->user()->associate($user);
    $cart->save();
    Log::info("✅ User associated to cart", ['cart_id' => $cart->id, 'user_id' => $user->id]);

    return redirect('/test');
});
// Route::get('/lunar/admin', function () {
//     if(!Auth::check()) {
//         // return redirect()->route('login');
//         return "not auth";
//     }
//     dd(Auth::user());
//     return "hello";
// })->name('lunar.admin');

Route::get('/reset', function () {
    //    $user = User::where('email', 'your@email.com')->first();

    // if ($user) {
    //     $user->password = Hash::make('newpassword123'); // set new password
    //     $user->save();

    //     return "✅ Password reset for {$user->email}";
    // }
    $bycrypt = Hash::make('password123');
    dd($bycrypt);

    return "❌ User not found.";
});



Route::get('/make', function () {
    Artisan::call('cache:clear');
    Artisan::call('config:clear');
    Artisan::call('route:clear');
    Artisan::call('view:clear');
    Artisan::call('optimize:clear');
    Artisan::call('storage:link');
    return "✅ storage link created.";
});

// use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\File;

Route::get('/reset-storage', function () {
    $link = public_path('storage');
    $source = storage_path('app/uploads'); // <-- change this to your actual image folder

    // 1. Remove old symlink
    if (is_link($link) || File::exists($link)) {
        File::delete($link);
    }

    // 2. Recreate symlink
    $exitCode = Artisan::call('storage:link');

    // 3. Copy images from source to public storage
    if (File::exists($source)) {
        File::copyDirectory($source, storage_path('app/public'));
    }

    // 4. Verify symlink and files
    $files = glob(public_path('storage') . '/*');

    if ($exitCode === 0 && count($files)) {
        return "✅ Storage link recreated and files copied successfully: " . implode(', ', array_map('basename', $files));
    }

    return "❌ Storage link created, but no files found!";
});

Route::get('/check-storage', function () {
    $link = public_path('storage');
    $files = glob($link . '/*');

    if (count($files)) {
        return "✅ Storage has files: " . implode(', ', array_map('basename', $files));
    }
    return "❌ Storage is empty!";
});
