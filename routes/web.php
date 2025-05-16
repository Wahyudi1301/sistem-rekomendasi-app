<?php

use Illuminate\Support\Facades\Route;
use Illuminate\Support\Facades\Auth; // Pastikan Auth di-import jika digunakan di closure

/*
|--------------------------------------------------------------------------
| Import Controllers
|--------------------------------------------------------------------------
*/
// == Admin Controllers ==
use App\Http\Controllers\Admin\LoginController as AdminLoginController;
use App\Http\Controllers\Admin\DashboardController as AdminDashboardController;
use App\Http\Controllers\Admin\BrandController as AdminBrandController;
use App\Http\Controllers\Admin\ItemController as AdminItemController;
use App\Http\Controllers\Admin\CategoryController as AdminCategoryController;
use App\Http\Controllers\Admin\CustomerController as AdminCustomerController;
use App\Http\Controllers\Admin\PaymentController as AdminPaymentController;
use App\Http\Controllers\Admin\StoreController as AdminStoreController; // <--- Tambahkan ini
use App\Http\Controllers\Admin\OrderController as AdminOrderController; // <--- Tambahkan ini
// Impor controller baru
use App\Http\Controllers\Admin\KeywordController; // <--- PASTIKAN NAMESPACE INI BENAR
use App\Http\Controllers\Admin\RecommendationConfigurationController; // <--- PASTIKAN NAMESPACE INI BENAR
use App\Http\Controllers\Admin\UserProfileController as AdminUserProfileController;
use App\Http\Controllers\Admin\UserController as AdminUserController;
use App\Http\Controllers\Admin\ServiceCostController as AdminServiceCostController;

// == Customer Controllers ==
use App\Http\Controllers\Customer\LoginController as CustomerLoginController;
use App\Http\Controllers\Customer\RegisterController as CustomerRegisterController;
use App\Http\Controllers\Customer\DashboardController as CustomerDashboardController;
use App\Http\Controllers\Customer\CatalogController;
use App\Http\Controllers\Customer\CartController;
use App\Http\Controllers\Customer\PaymentController as CustomerPaymentController;
use App\Http\Controllers\Customer\ProfileController as CustomerProfileController;
use App\Http\Controllers\Customer\OrderController as CustomerOrderController;

// == Webhook Controller ==
use App\Http\Controllers\Webhook\MidtransController as MidtransWebhookController;


/*
|--------------------------------------------------------------------------
| Rute Landing Page / Umum
|--------------------------------------------------------------------------
*/

Route::get('/', function () {
    // Jika sudah login sebagai customer, arahkan ke dashboard
    if (Auth::guard('customer')->check()) {
        return redirect()->route('customer.dashboard');
    }
    // Jika belum, arahkan ke halaman login customer
    return redirect()->route('customer.login');
})->name('landing');


/*
|==========================================================================
| ADMIN ROUTES
|==========================================================================
*/
Route::prefix('admin')->name('admin.')->group(function () {
    Route::middleware('guest')->group(function () {
        Route::get('/login', [AdminLoginController::class, 'showLogin'])->name('login');
        Route::post('/login', [AdminLoginController::class, 'login'])->name('login.post');
    });

    Route::middleware('auth')->group(function () {
        Route::post('/logout', [AdminLoginController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [AdminDashboardController::class, 'index'])->name('dashboard');

        Route::prefix('account')->name('account.')->group(function () {
            Route::get('/profile/edit', [AdminUserProfileController::class, 'edit'])->name('profile.edit');
            Route::put('/profile/update', [AdminUserProfileController::class, 'update'])->name('profile.update');
        });

        Route::get('/store/edit', [AdminStoreController::class, 'edit'])->name('store.edit');
        Route::put('/store/update', [AdminStoreController::class, 'update'])->name('store.update');

        Route::prefix('users')->name('users.')->group(function () {
            Route::get('/', [AdminUserController::class, 'index'])->name('index');
            Route::get('/data', [AdminUserController::class, 'getData'])->name('data');
            Route::get('/create', [AdminUserController::class, 'create'])->name('create');
            Route::post('/', [AdminUserController::class, 'store'])->name('store');
            Route::get('/{user:hashid}/edit', [AdminUserController::class, 'edit'])->name('edit');
            Route::put('/{user:hashid}', [AdminUserController::class, 'update'])->name('update');
            Route::delete('/{user:hashid}', [AdminUserController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('brands')->name('brands.')->group(function () {
            Route::get('/', [AdminBrandController::class, 'index'])->name('index');
            Route::get('/data', [AdminBrandController::class, 'getData'])->name('data');
            Route::get('/create', [AdminBrandController::class, 'create'])->name('create');
            Route::post('/', [AdminBrandController::class, 'store'])->name('store');
            Route::get('/{brand_hash}/edit', [AdminBrandController::class, 'edit'])->name('edit')->where('brand_hash', '[a-zA-Z0-9]+');
            Route::put('/{brand_hash}', [AdminBrandController::class, 'update'])->name('update')->where('brand_hash', '[a-zA-Z0-9]+');
            Route::delete('/{brand_hash}', [AdminBrandController::class, 'destroy'])->name('destroy')->where('brand_hash', '[a-zA-Z0-9]+');
        });

        Route::prefix('items')->name('items.')->group(function () {
            Route::get('/', [AdminItemController::class, 'index'])->name('index');
            Route::get('/data', [AdminItemController::class, 'getData'])->name('data');
            Route::get('/create', [AdminItemController::class, 'create'])->name('create');
            Route::post('/', [AdminItemController::class, 'store'])->name('store');
            Route::get('/{item:hashid}/edit', [AdminItemController::class, 'edit'])->name('edit');
            Route::put('/{item:hashid}', [AdminItemController::class, 'update'])->name('update');
            Route::delete('/{item:hashid}', [AdminItemController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('keywords')->name('keywords.')->group(function () {
            Route::get('/', [KeywordController::class, 'index'])->name('index');
            Route::get('/data', [KeywordController::class, 'getData'])->name('data');
            Route::get('/create', [KeywordController::class, 'create'])->name('create');
            Route::post('/', [KeywordController::class, 'store'])->name('store');
            Route::get('/{keyword:hashid}/edit', [KeywordController::class, 'edit'])->name('edit');
            Route::put('/{keyword:hashid}', [KeywordController::class, 'update'])->name('update');
            Route::delete('/{keyword:hashid}', [KeywordController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('recommendation-configurations')->name('recommendation_configurations.')->group(function () {
            Route::get('/', [RecommendationConfigurationController::class, 'index'])->name('index');
            Route::get('/data', [RecommendationConfigurationController::class, 'getData'])->name('data');
            Route::get('/create', [RecommendationConfigurationController::class, 'create'])->name('create');
            Route::post('/', [RecommendationConfigurationController::class, 'store'])->name('store');
            Route::get('/{configuration:hashid}/edit', [RecommendationConfigurationController::class, 'edit'])->name('edit');
            Route::put('/{configuration:hashid}', [RecommendationConfigurationController::class, 'update'])->name('update');
            Route::delete('/{configuration:hashid}', [RecommendationConfigurationController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('categories')->name('categories.')->group(function () {
            Route::get('/', [AdminCategoryController::class, 'index'])->name('index');
            Route::get('/data', [AdminCategoryController::class, 'getData'])->name('data');
            Route::get('/create', [AdminCategoryController::class, 'create'])->name('create');
            Route::post('/', [AdminCategoryController::class, 'store'])->name('store');
            Route::get('/{category:hashid}/edit', [AdminCategoryController::class, 'edit'])->name('edit');
            Route::put('/{category:hashid}', [AdminCategoryController::class, 'update'])->name('update');
            Route::delete('/{category:hashid}', [AdminCategoryController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('customers')->name('customers.')->group(function () {
            Route::get('/', [AdminCustomerController::class, 'index'])->name('index');
            Route::get('/data', [AdminCustomerController::class, 'getData'])->name('data');
            Route::get('/create', [AdminCustomerController::class, 'create'])->name('create');
            Route::post('/', [AdminCustomerController::class, 'store'])->name('store');
            Route::get('/{customer:hashid}/edit', [AdminCustomerController::class, 'edit'])->name('edit');
            Route::put('/{customer:hashid}', [AdminCustomerController::class, 'update'])->name('update');
            Route::delete('/{customer:hashid}', [AdminCustomerController::class, 'destroy'])->name('destroy');
        });

        Route::prefix('payments')->name('payments.')->group(function () {
            Route::get('/', [AdminPaymentController::class, 'index'])->name('index');
            Route::get('/data', [AdminPaymentController::class, 'getData'])->name('data');
            Route::get('/{payment:hashid}/edit', [AdminPaymentController::class, 'edit'])->name('edit'); // Admin mungkin hanya bisa lihat, atau update status manual jika error gateway
            Route::put('/{payment:hashid}', [AdminPaymentController::class, 'update'])->name('update');
            Route::get('/{payment:hashid}', [AdminPaymentController::class, 'show'])->name('show');
        });

        // === ROUTE UNTUK ORDERS (Menggantikan Bookings) ===
        Route::prefix('orders')->name('orders.')->group(function () {
            Route::get('/', [AdminOrderController::class, 'index'])->name('index');
            Route::get('/data', [AdminOrderController::class, 'getData'])->name('data');
            Route::get('/{order:hashid}', [AdminOrderController::class, 'show'])->name('show');
            Route::get('/{order:hashid}/edit-status', [AdminOrderController::class, 'editStatus'])->name('editStatus'); // Form edit status order
            Route::put('/{order:hashid}/update-status', [AdminOrderController::class, 'updateStatus'])->name('updateStatus'); // Proses update status order
            Route::get('/{order:hashid}/print', [AdminOrderController::class, 'printOrder'])->name('print'); // Ganti nama method jika perlu
            // Jika admin bisa menandai pembayaran cash sebagai 'paid':
            Route::post('/{order:hashid}/mark-cash-paid', [AdminOrderController::class, 'markCashAsPaid'])->name('markCashPaid');
        });
        // =================================================

        Route::prefix('service-costs')->name('service-costs.')->group(function () {
            Route::get('/', [AdminServiceCostController::class, 'index'])->name('index');
            Route::get('/data', [AdminServiceCostController::class, 'getData'])->name('data'); // Route untuk DataTables
            Route::get('/create', [AdminServiceCostController::class, 'create'])->name('create');
            Route::post('/', [AdminServiceCostController::class, 'store'])->name('store');
            Route::get('/{service_cost_hash}/edit', [AdminServiceCostController::class, 'edit'])->name('edit')->where('service_cost_hash', '[a-zA-Z0-9]+');
            Route::put('/{service_cost_hash}', [AdminServiceCostController::class, 'update'])->name('update')->where('service_cost_hash', '[a-zA-Z0-9]+');
            Route::delete('/{service_cost_hash}', [AdminServiceCostController::class, 'destroy'])->name('destroy')->where('service_cost_hash', '[a-zA-Z0-9]+'); // Jika ada delete
        });
    });
});

/*
|==========================================================================
| CUSTOMER ROUTES
|==========================================================================
*/
Route::name('customer.')->group(function () {

    Route::middleware('guest:customer')->group(function () {
        Route::get('/login', [CustomerLoginController::class, 'showLoginForm'])->name('login');
        Route::post('/login', [CustomerLoginController::class, 'login'])->name('login.post');
        Route::get('/register', [CustomerRegisterController::class, 'showRegistrationForm'])->name('register');
        Route::post('/register', [CustomerRegisterController::class, 'register'])->name('register.post');
    });

    Route::middleware('auth:customer')->group(function () {
        Route::post('/logout', [CustomerLoginController::class, 'logout'])->name('logout');
        Route::get('/dashboard', [CustomerDashboardController::class, 'index'])->name('dashboard');

        Route::get('/catalog', [CatalogController::class, 'index'])->name('catalog.index');
        Route::get('/item/{item_hash}', [CatalogController::class, 'show'])
            ->name('catalog.show')
            ->where('item_hash', '[a-zA-Z0-9]+');

        Route::prefix('cart')->name('cart.')->group(function () {
            Route::get('/', [CartController::class, 'index'])->name('index');
            Route::post('/add', [CartController::class, 'add'])->name('add');
            // Pastikan route update menggunakan PUT jika Anda memakai _method di form AJAX
            // Jika tidak, POST saja cukup asal controller menghandlenya.
            Route::match(['put', 'post'], '/update', [CartController::class, 'update'])->name('update');
            // Route remove bisa POST atau DELETE (dengan _method)
            Route::match(['delete', 'post'], '/remove', [CartController::class, 'remove'])->name('remove');
        });

        // Order & Payment Process Routes
        Route::post('/order/place', [CustomerOrderController::class, 'processOrder'])->name('order.place'); // GANTI

        Route::prefix('payment')->name('payment.')->group(function () {
            // Parameter sekarang 'order_hashid'
            Route::get('/initiate/{order_hashid}', [CustomerPaymentController::class, 'initiatePayment'])->name('initiate')->where('order_hashid', '[a-zA-Z0-9]+');
            Route::get('/show/{order_hashid}', [CustomerPaymentController::class, 'showPaymentPage'])->name('show')->where('order_hashid', '[a-zA-Z0-9]+');
            Route::get('/finished/{order_hashid}', [CustomerPaymentController::class, 'paymentFinished'])->name('finished')->where('order_hashid', '[a-zA-Z0-9]+');
        });

        Route::prefix('my-orders')->name('orders.')->group(function () { // GANTI
            Route::get('/', [CustomerOrderController::class, 'myOrders'])->name('index'); // GANTI
            Route::get('/data', [CustomerOrderController::class, 'getMyOrdersData'])->name('data'); // GANTI
            Route::get('/{order_hashid}', [CustomerOrderController::class, 'showMyOrder']) // GANTI
                ->name('show')
                ->where('order_hashid', '[a-zA-Z0-9]+');
        });

        Route::prefix('profile')->name('profile.')->group(function () {
            Route::get('/edit', [CustomerProfileController::class, 'edit'])->name('edit');
            Route::put('/update', [CustomerProfileController::class, 'update'])->name('update');
        });
    });
});
