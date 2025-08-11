<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ServiceFeesController;
use App\Http\Controllers\Admin\ServiceTaxesController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\IdConfigController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Auth\AuthController;
use App\Http\Controllers\InternalRevenueServiceController;
use App\Http\Controllers\NotificationController;
use App\Http\Controllers\Platforms\FileUploadController;
use App\Http\Controllers\Platforms\PaymentController;
use App\Http\Controllers\StateController;
use App\Http\Controllers\User\FeesController;
use App\Http\Controllers\User\SettingsController;
use App\Http\Controllers\User\TaxesController;
use App\Http\Controllers\User\UserController;
use Illuminate\Support\Facades\Route;

// Public routes
Route::prefix('onboarding')->group(function () {
    Route::get('', [OnboardingController::class, 'index']);
    Route::put('', [OnboardingController::class, 'UpdateOnboardingSection'])
        ->middleware(['auth:admin', 'role:admin,operator']);
});

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('otp/send', [AuthController::class, 'sendOTP']);
    Route::post('otp/verify', [AuthController::class, 'verifyOTP']);
});

// Authenticated routes - User and Admin guards
Route::middleware(['api', 'auth:api,admin'])->group(function () {
    // Auth protected
    Route::prefix('auth')->group(function () {
        Route::post('logout', [AuthController::class, 'logout']);
    });

    // Dashboard
    Route::prefix('dashboard')->group(function () {
        Route::get('stats', [AdminDashboardController::class, 'getDashboardStats'])
            ->middleware('role:admin,operator');
    });

    // Transactions
    Route::prefix('transactions')->group(function () {
        Route::get('admin', [UserController::class, 'getAllTransactions'])
            ->middleware('role:admin,operator');
        Route::get('', [PaymentController::class, 'getAllTransactions'])   
            ->middleware('role:admin,operator');
        Route::get('user', [PaymentController::class, 'getUserTransactions'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('{invoiceNumber}', [PaymentController::class, 'getTransaction'])
            ->middleware('role:admin,operator,irs_specialist,user');
    });

    // Users
    Route::prefix('users')->group(function () {
        Route::get('/activity', [UserController::class, 'getActivities']);
        Route::get('/{id}', [UserController::class, 'getUser']);
        Route::get('/greeting', [UserController::class, 'greetUser']);
        Route::put('/{id}', [UserController::class, 'updateUser']);
            // Admin/Operator only
            Route::middleware('role:admin,operator')->group(function () {
                Route::get('/', [UserController::class, 'getAllUsers']);
                Route::post('/activity/new', [UserController::class, 'createActivity']);
                Route::patch('/activity/{id}', [UserController::class, 'updateActivity']);
                Route::delete('/activity/{id}', [UserController::class, 'deleteActivity']);
                Route::delete('/{id}', [UserController::class, 'dropUser']);
        });

        // User profile (accessible by all authenticated users)
        Route::middleware('role:admin,operator,irs_specialist,user')->group(function () {
            Route::get('/profile', [UserController::class, 'getProfile']);
            Route::put('/profile', [UserController::class, 'updateProfile']);
        });

        // User activities (taxes and fees)
        Route::prefix('/activity')->group(function () {
            // IRS specialist specific routes
            Route::get('/taxes', [TaxesController::class, 'getUserTaxes']);
            Route::post('/tax', [TaxesController::class, 'checkoutTax']);
            Route::get('/tax/{id}', [TaxesController::class, 'getUserTax']);
            Route::patch('/tax/{id}', [TaxesController::class, 'updateUserTax']);
            Route::get('/fees', [FeesController::class, 'getUserFees']);
            Route::post('/checkout-fee', [FeesController::class, 'checkoutFee']);
            Route::get('/fee/{id}', [FeesController::class, 'getUserFee']);
            Route::patch('/fee/{id}', [FeesController::class, 'updateUserFee']);
            Route::middleware('role:admin,operator,irs_specialist')->group(function () {
                Route::delete('/tax/{id}', [TaxesController::class, 'dropUserTax']);
                Route::delete('/fee/{id}', [FeesController::class, 'dropUserFee']);
            });
            
            // User specific routes
            Route::middleware('role:user')->group(function () {
                Route::get('my-taxes', [TaxesController::class, 'getMyTaxes']);
                Route::get('my-fees', [FeesController::class, 'getMyFees']);
            });
        });
    });

    // Platforms
    Route::prefix('platforms')->group(function () {
        Route::post('/invoices', [PaymentController::class, 'createInvoice']);
        Route::get('/invoices', [PaymentController::class, 'getAllInvoices']);
        Route::get('/invoices/user', [PaymentController::class, 'getInvoices'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('/invoices/{invoiceNumber}', [PaymentController::class, 'getInvoiceByInvoiceNumber'])
            ->middleware('role:admin,operator,irs_specialist,user');

        // Payment Routes
        Route::post('/payments', [PaymentController::class, 'processPayment']);
        Route::get('/payments', [PaymentController::class, 'getAllPayments']);
        Route::get('/payments/user', [PaymentController::class, 'getPayments'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('/payments/{invoiceNumber}', [PaymentController::class, 'getPaymentByInvoiceNumber'])
            ->middleware('role:admin,operator,irs_specialist,user');
    });

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('/', [SettingsController::class, 'getSettings']);
        Route::get('/language', [SettingsController::class, 'getLanguage']);
        Route::get('/languages', [SettingsController::class, 'getLanguages']);
        Route::post('/', [SettingsController::class, 'updateSettings']);
        Route::post('/language', [SettingsController::class, 'updateLanguage']);
    });

    // Organizations
    Route::prefix('organizations')->group(function () {
        Route::get('', [OrganizationController::class, 'getOrganizations'])
            ->middleware('role:admin,operator,user');
        Route::get('{id}', [OrganizationController::class, 'getOrganization'])
            ->middleware('role:admin,operator,irs_specialist,user');
        
        Route::middleware('role:admin')->group(function () {
            Route::post('', [OrganizationController::class, 'createOrganization']);
            Route::put('{id}', [OrganizationController::class, 'updateOrganization']);
            Route::delete('{id}', [OrganizationController::class, 'deleteOrganization']);
        });

        // ID Configurations
        Route::get('/{orgId}/id-configs', [IdConfigController::class, 'index'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('/{orgId}/id-configs/{idConfig}', [IdConfigController::class, 'show'])
            ->middleware('role:admin,operator,irs_specialist,user');
        
        // ID Configurations - WRITE operations (admin only)
        Route::middleware('role:admin')->group(function () {
            Route::post('/{orgId}/id-configs', [IdConfigController::class, 'store']);
            Route::put('/{orgId}/id-configs/{idConfig}', [IdConfigController::class, 'update']);
            Route::delete('/{orgId}/id-configs/{idConfig}', [IdConfigController::class, 'destroy']);
            Route::post('/{orgId}/id-configs/reorder', [IdConfigController::class, 'reorder']);
        });
        
        // Field types endpoint (all roles)
        Route::get('/id-configs/field-types', [IdConfigController::class, 'getFieldTypes'])
            ->middleware('role:admin,operator,irs_specialist,user');
    });

    // Services
    Route::prefix('services')->group(function () {
        // Public read-only endpoints
        Route::get('/fees', [ServiceFeesController::class, 'getServices'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('/fees/{id}', [ServiceFeesController::class, 'getServiceFee'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('/organizations/{organizationId}/services', [ServiceFeesController::class, 'getOrganizationServices'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('taxes', [ServiceTaxesController::class, 'getServiceTaxes'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('taxes/{id}', [ServiceTaxesController::class, 'getServiceTax'])
            ->middleware('role:admin,operator,irs_specialist,user');

        // Admin/Operator management
        Route::middleware('role:admin,operator')->group(function () {
            // Fees
            Route::post('fees', [ServiceFeesController::class, 'createServiceFee']);
            Route::put('fees/{id}', [ServiceFeesController::class, 'updateServiceFee']);
            
            // Taxes
            Route::post('taxes', [ServiceTaxesController::class, 'createServiceTax']);
            Route::put('taxes/{id}', [ServiceTaxesController::class, 'updateServiceTax']);
        });
        
        // Admin-only management
        Route::middleware('role:admin')->group(function () {
            Route::delete('fees/{id}', [ServiceFeesController::class, 'deleteServiceFee']);
            Route::delete('taxes/{id}', [ServiceTaxesController::class, 'deleteServiceTax']);
        });
    });

    // Notifications
    Route::prefix('notifications')->group(function () {
        // Admin routes
        Route::middleware('role:admin,operator')->group(function () {
            Route::get('/', [NotificationController::class, 'index']);
            Route::post('/', [NotificationController::class, 'store']);
            Route::get('/{id}', [NotificationController::class, 'show']);
            Route::put('/{id}', [NotificationController::class, 'update']);
            Route::delete('/{id}', [NotificationController::class, 'destroy']);
            Route::post('/bulk-register-users', [NotificationController::class, 'bulkRegisterUsers']);
        });

        // User notifications
        Route::middleware('role:admin,operator,irs_specialist,user')->group(function () {
            Route::get('/user/my-notifications', [NotificationController::class, 'getUserNotifications']);
            Route::post('/user/{notificationId}/mark-read', [NotificationController::class, 'markAsRead']);
            Route::post('/user/tokens', [NotificationController::class, 'updateTokens']);
            Route::post('/user/preferences', [NotificationController::class, 'updatePreferences']);
            Route::get('/user/status', [NotificationController::class, 'getNotificationStatus']);
            Route::post('/user/register-onesignal', [NotificationController::class, 'registerWithOneSignal']);
            Route::post('/user/subscribe-push', [NotificationController::class, 'subscribeToPush']);
        });

        // System routes (admin only)
        Route::post('/process-scheduled', [NotificationController::class, 'processScheduled'])
            ->middleware('role:admin');
    });
    
    // States
    Route::prefix('states')->group(function () {
        Route::get('/', [StateController::class, 'index'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('/{id}', [StateController::class, 'show'])
            ->middleware('role:admin,operator,irs_specialist,user');
        
        Route::middleware('role:admin,operator')->group(function () {
            Route::post('/', [StateController::class, 'store']);
            Route::put('/{id}', [StateController::class, 'update']);
        });
        
        Route::middleware('role:admin')->group(function () {
            Route::delete('/{id}', [StateController::class, 'destroy']);
        });
    });
    
    // IRS
    Route::prefix('irs')->group(function () {
        Route::get('/', [InternalRevenueServiceController::class, 'index'])
            ->middleware('role:admin,operator,irs_specialist,user');
        
        Route::middleware('role:admin,operator,irs_specialist')->group(function () {
            Route::post('/', [InternalRevenueServiceController::class, 'store']);
            Route::get('/{id}', [InternalRevenueServiceController::class, 'show']);
            Route::put('/{id}', [InternalRevenueServiceController::class, 'update']);
        });
        
        Route::middleware('role:admin')->group(function () {
            Route::delete('/{id}', [InternalRevenueServiceController::class, 'destroy']);
            Route::post('/bulk-import', [InternalRevenueServiceController::class, 'bulkImport']);
        });
    });
    
    // File Uploads
    Route::prefix('platforms')->group(function () {
        Route::post('media/upload', [FileUploadController::class, 'upload'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::delete('media/delete', [FileUploadController::class, 'delete'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('media/list', [FileUploadController::class, 'list'])
            ->middleware('role:admin,operator,irs_specialist,user');
        Route::get('media/details', [FileUploadController::class, 'details'])
            ->middleware('role:admin,operator,irs_specialist,user');
    });
});

// OAuth
Route::prefix('auth/oauth')->group(function () {
    // Route::get('{provider}', [OAuthController::class, 'redirectToProvider']);
    // Route::get('{provider}/callback', [OAuthController::class, 'handleProviderCallback']);
});

// Testing routes (remove in production)
if (app()->environment('local')) {
    Route::delete('users-smackdown', [UserController::class, 'smackUserDB']);
    Route::delete('user-smackdown', [UserController::class, 'smackUser']);
}

Route::get('connction-test', function () {
   try {
        DB::connection()->getPdo();
        echo "Connected successfully to: " . DB::connection()->getDatabaseName();
    } catch (\Exception $e) {
        die("Could not connect to the database: " . $e->getMessage());
   }
});

// User Management Routes
Route::prefix('roles')->middleware(['auth:api,admin'])->group(function() {
    // User CRUD operations
    Route::get('/users', [UserManagementController::class, 'index'])
        ->middleware('role:admin,operator');
    Route::post('/users', [UserManagementController::class, 'store'])
        ->middleware('role:admin');
    Route::put('/users/{id}', [UserManagementController::class, 'update'])
        ->middleware('role:admin,operator');
    Route::patch('/users/{id}/status', [UserManagementController::class, 'updateStatus'])
        ->middleware('role:admin,operator');
    Route::delete('/users/{id}', [UserManagementController::class, 'destroy'])
        ->middleware('role:admin');

    // Helper routes for form data
    Route::get('/users/states', [UserManagementController::class, 'getStates'])
        ->middleware('role:admin,operator');
    Route::get('/', [UserManagementController::class, 'getRoles'])
        ->middleware('role:admin,operator');
});