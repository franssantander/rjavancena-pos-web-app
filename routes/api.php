<?php

use App\Helper\Helper;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Route;
use App\Http\Controllers\LogController;
use App\Http\Controllers\AuthController;
use App\Http\Controllers\AccountController;
use App\Http\Controllers\PaymentController;
use App\Http\Controllers\VoucherController;
use App\Http\Controllers\ExpensesController;
use App\Http\Controllers\PurchaseController;
use App\Http\Controllers\UserInfoController;
use App\Http\Controllers\DashboardController;
use App\Http\Controllers\InventoryController;
use App\Http\Controllers\VoucherItemController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\ExpenesesImageController;
use App\Http\Controllers\InventoryProductController;
use App\Http\Controllers\InventoryProductLostController;
use App\Http\Controllers\InventoryProductFoundController;
use App\Http\Controllers\InventoryProductRestockController;
use App\Http\Controllers\InventoryProductLostImageController;
use App\Http\Controllers\InventoryProductFoundImageController;
use App\Http\Controllers\InventoryProductRestockImageController;

/*
|--------------------------------------------------------------------------
| API Routes
|--------------------------------------------------------------------------
|
| Here is where you can register API routes for your application. These
| routes are loaded by the RouteServiceProvider and all of them will
| be assigned to the "api" middleware group. Make something great!
|
*/

// Route::middleware('auth:sanctum')->get('/user', function (Request $request) {
//     return $request->user();
// });

Route::get('/index-history', [AuthController::class, 'indexHistory']);
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

Route::get('/eu-device', [Helper::class, 'userDevice']);

// Authenticated Users
Route::middleware(['jwt.auth'])->group(function () {
    // Checking token
    Route::get('/check-token', [AuthController::class, 'checkToken']);
    Route::get('/role-nav-links', [AuthController::class, 'roleNavLinks']);
    Route::post('/logout', [AuthController::class, 'logout']);

    // Register
    Route::group(["prefix" => "signup"], function () {
        Route::controller(AuthController::class)->group(function () {
            Route::post("verify-email", 'verifyEmail');
            Route::post("resend-code", 'resendVerificationAuth');
            // Route::post("resend-code", 'resendVerificationAuth')->middleware('countdown.limiter');
        });
    });

    // Update Password
    Route::group(["prefix" => "new-password"], function () {
        Route::controller(AuthController::class)->group(function () {
            Route::post("update-password", 'updatePassword');
        });
    });

    // User Accounts
    Route::group(["prefix" => "accounts"], function () {
        Route::controller(AccountController::class)->group(function () {
            // ADMIN ONLY
            Route::group(["prefix" => "admin"], function () {
                Route::get("index", 'index');
                Route::get('show/{id}', 'show');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::post('store-auth-user-info', 'storeAuthUserInfoAdmin');
                Route::post('update-user-info', 'updateUserInfoAdmin');
                Route::delete('destroy', 'destroy');
            });

            // USER ONLY
            Route::group(["prefix" => "user"], function () {
                Route::get('show/{id}', 'show');
                Route::post('update-email', 'updateEmailOnSettingUser');
                Route::post('update-password', 'updatePasswordOnSettingUser');
                Route::post('resend-code-email', 'resendVerificationCodeEmail');
                Route::post('resend-code-password', 'resendVerificationCodePassword');
            });
        });
    });

    // Personal User Information
    Route::group(["prefix" => "user-info"], function () {
        Route::controller(UserInfoController::class)->group(function () {
            Route::get('index', 'index');
            Route::post('store', 'store');
            Route::post('update', 'update');
            Route::post('update-image', 'updateImage');
            Route::get('get-personal-info', 'getPersonalInfo');

            Route::post('update-email', 'updateEmailOnSettingUser');
            Route::post('update-password', 'updatePasswordOnSettingUser');
            Route::post('resend-code-email', 'resendVerificationCodeEmail');
            Route::post('resend-code-password', 'resendVerificationCodePassword');
        });
    });

    // Inventory
    Route::group(["prefix" => "inventory"], function () {
        // PARENT
        Route::controller(InventoryController::class)->group(function () {
            Route::group(["prefix" => "parent"], function () {
                Route::get('index', 'index');
                Route::get('show/{id}/', 'show');
                Route::post('store', 'store');
                Route::post('store-multiple', 'storeMultiple');
                Route::get('edit/{id}', 'edit');
                Route::post('update', 'update');
                Route::post('update-multiple', 'updateMultiple');
                Route::delete('delete', 'destroy');
                Route::delete('delete-multiple', 'destroyMultiple');

                Route::get('product/show/{id}/', 'showProduct');
            });
        });

        // CHILD PRODUCT
        Route::group(["prefix" => "product"], function () {
            Route::controller(InventoryProductController::class)->group(function () {
                Route::get('index', 'index');

                Route::get('lost/show/{id}/', 'showLost');
                Route::get('found/show/{id}/', 'showFound');
                Route::get('restock/show/{id}/', 'showRestock');

                Route::post('store', 'store');
                Route::post('store-multiple', 'storeMultiple');
                Route::post('update', 'update');
                Route::post('update-multiple', 'updateMultiple');
                Route::delete('delete', 'destroy');
                Route::delete('delete-multiple', 'destroyMultiple');
            });

            // CHILD PRODUCT LOST
            Route::group(["prefix" => "lost"], function () {
                Route::controller(InventoryProductLostController::class)->group(function () {
                    Route::get('index', 'index');
                    Route::post('store', 'store');
                    Route::post('update', 'update');
                    Route::delete('delete', 'destroy');
                });


                // CHILD PRODUCT LOST IMAGE
                Route::group(["prefix" => "image"], function () {
                    Route::controller(InventoryProductLostImageController::class)->group(function () {
                        Route::get('index', 'index');
                        Route::post('store', 'store');
                        Route::post('update', 'update');
                        Route::delete('delete', 'destroy');
                    });
                });
            });

            // CHILD PRODUCT FOUND
            Route::group(["prefix" => "found"], function () {
                Route::controller(InventoryProductFoundController::class)->group(function () {
                    Route::get('index', 'index');
                    Route::post('store', 'store');
                    Route::post('update', 'update');
                    Route::delete('delete', 'destroy');
                });

                // CHILD PRODUCT FOUND IMAGE
                Route::group(["prefix" => "image"], function () {
                    Route::controller(InventoryProductFoundImageController::class)->group(function () {
                        Route::get('index', 'index');
                        Route::post('store', 'store');
                        Route::post('update', 'update');
                        Route::delete('delete', 'destroy');
                    });
                });
            });

            // CHILD PRODUCT RESTOCK
            Route::group(["prefix" => "restock"], function () {
                Route::controller(InventoryProductRestockController::class)->group(function () {
                    Route::get('index', 'index');
                    Route::post('store', 'store');
                    Route::post('update', 'update');
                    Route::delete('delete', 'destroy');
                });

                // CHILD PRODUCT RESTOCK IMAGE
                Route::group(["prefix" => "image"], function () {
                    Route::controller(InventoryProductRestockImageController::class)->group(function () {
                        Route::get('index', 'index');
                        Route::post('store', 'store');
                        Route::post('update', 'update');
                        Route::delete('delete', 'destroy');
                    });
                });
            });
        });
    });

    // Purchase
    Route::group(["prefix" => "purchase"], function () {
        Route::controller(PurchaseController::class)->group(function () {
            Route::get('get-user-id-menu-costumer', 'getUserIdMenuCustomer'); // Refactor
            Route::post('store', 'store');
            Route::post('minus-qty', 'minusQty');
            Route::post('add-qty', 'addQty');
            Route::delete('delete-all-qty', 'deleteQtyAll');
            Route::post('update-qty', 'updateQty');
            Route::post('update-name', 'updateCustomerName');
            Route::delete('delete-customer', 'deleteCustomer');

            Route::post('add-voucher', 'addVoucher');
            Route::post('update-voucher', 'updateVoucher');
            Route::delete('delete-voucher', 'destroyVoucher');

            Route::get('get-voucher-uses/{id}', 'getVoucherUses');
        });
    });

    // Payment
    Route::group(["prefix" => "payment"], function () {
        Route::controller(PaymentController::class)->group(function () {
            Route::post('payment', 'payment');
            Route::post('receipt', 'receipt');
        });
    });

    // Dashboard
    Route::group(["prefix" => "dashboard"], function () {
        Route::controller(DashboardController::class)->group(function () {
            Route::get('/', 'dashboard');
            Route::post('update-status-void', 'updateStatusVoid');
            Route::get('get-recent-transaction-cashier', 'getRecentTransactionCashierRole');
        });
    });

    // Log
    Route::group(["prefix" => "log"], function () {
        Route::controller(LogController::class)->group(function () {
            Route::get('index', 'index');
        });
    });

    // Voucher
    Route::group(["prefix" => "voucher"], function () {
        // PARENT
        Route::controller(VoucherController::class)->group(function () {
            Route::group(["prefix" => "parent"], function () {
                Route::get('index', 'index');
                Route::get('items/show/{id}', 'showVoucherItemChild');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('delete', 'destroy');
            });
        });

        // CHILD VOUCHER
        Route::controller(VoucherItemController::class)->group(function () {
            Route::group(["prefix" => "item"], function () {
                Route::get('index', 'index');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('delete', 'destroy');
            });
        });
    });

    // Expenses
    Route::group(["prefix" => "expenses"], function () {
        Route::controller(ExpensesController::class)->group(function () {
            Route::group(["prefix" => "expense"], function () {
                Route::get('index', 'index');
                Route::get('show/{id}', 'showExpensesImageChild');
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('delete', 'destroy');
            });
        });

        Route::controller(ExpenesesImageController::class)->group(function () {
            Route::group(["prefix" => "expense-image"], function () {
                Route::post('store', 'store');
                Route::post('update', 'update');
                Route::delete('delete', 'destroy');
            });
        });
    });

    // Notification
    Route::group(["prefix" => "notification"], function () {
        Route::controller(NotificationController::class)->group(function () {
            Route::get('get-notification', 'getNotificationByUser');
            Route::post('update-view', 'updateView');
        });
    });
});
