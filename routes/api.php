<?php

use App\Http\Controllers\Admin\AdminDashboardController;
use App\Http\Controllers\Admin\ServiceFeesController;
use App\Http\Controllers\Admin\ServiceTaxesController;
use App\Http\Controllers\Admin\OrganizationController;
use App\Http\Controllers\Admin\UserManagementController;
use App\Http\Controllers\IdConfigController;
use App\Http\Controllers\Api\OnboardingController;
use App\Http\Controllers\Auth\AuthController;
// use App\Http\Controllers\Auth\OAuthController;
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

    // Admin/Operator only
    Route::put('', [OnboardingController::class, 'UpdateOnboardingSection'])->middleware(['auth:admin', 'role:admin,operator']);
});

Route::prefix('auth')->group(function () {
    Route::post('register', [AuthController::class, 'register']);
    Route::post('login', [AuthController::class, 'login']);
    Route::post('forgot-password', [AuthController::class, 'forgotPassword']);
    Route::post('reset-password', [AuthController::class, 'resetPassword']);
    Route::post('otp/send', [AuthController::class, 'sendOTP']);
    Route::post('otp/verify', [AuthController::class, 'verifyOTP']);
});


// Authenticated routes
Route::middleware(['api', 'auth:admin'])->group(function () {
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
        Route::get('user', [UserController::class, 'getAllTransactions'])
            ->middleware('role:admin,operator');
    });

    // Users
    Route::prefix('users')->group(function () {
        Route::get('activity', [UserController::class, 'getActivities'])
            ->middleware('role:admin,operator,irs_specialist');
        Route::get('{id}', [UserController::class, 'getUser'])
            ->middleware('role:admin,operator');
        Route::get('', [UserController::class, 'getAllUsers'])
            ->middleware('role:admin,operator');
        Route::post('activity/new', [UserController::class, 'createActivity'])
            ->middleware('role:admin,operator');

        // Admin/Operator only
        Route::middleware('role:admin,operator')->group(function () {
            Route::patch('activity/{id}', [UserController::class, 'updateActivity']);
            Route::delete('activity/{id}', [UserController::class, 'deleteActivity']);
            Route::get('greeting', [UserController::class, 'greetUser']);
            Route::delete('{id}', [UserController::class, 'dropUser']);
            Route::put('{id}', [UserController::class, 'updateUser']);
        });

        // User activities (taxes and fees)
        Route::prefix('activity')->group(function () {
            // IRS specialist specific routes
            Route::middleware('role:irs_specialist')->group(function () {
                Route::get('taxes', [TaxesController::class, 'getUserTaxes']);
                Route::post('tax', [TaxesController::class, 'checkoutTax']);
                Route::get('tax/{id}', [TaxesController::class, 'getUserTax']);
                Route::patch('tax/{id}', [TaxesController::class, 'updateUserTax']);
                Route::delete('tax/{id}', [TaxesController::class, 'dropUserTax']);
            });

            // Admin/Operator specific routes
            Route::middleware('role:admin,operator')->group(function () {
                Route::get('fees', [FeesController::class, 'getUserFees']);
                Route::post('checkout-fee', [FeesController::class, 'checkoutFee']);
                Route::get('fee/{id}', [FeesController::class, 'getUserFee']);
                Route::patch('fee/{id}', [FeesController::class, 'updateUserFee']);
                Route::delete('fee/{id}', [FeesController::class, 'dropUserFee']);
            });
        });
    });

    // Platforms
    Route::prefix('platforms')->group(function () {
        Route::post('/invoices', [PaymentController::class, 'createInvoice'])
            ->middleware('role:admin,operator');
        Route::get('/invoices', [PaymentController::class, 'getAllInvoices'])
            ->middleware('role:admin,operator');
        Route::get('/invoices/user', [PaymentController::class, 'getInvoices'])
            ->middleware('role:admin,operator,irs_specialist');
        Route::get('/invoices/{invoiceNumber}', [PaymentController::class, 'getInvoice'])
            ->middleware('role:admin,operator,irs_specialist');

        // Payment Routes
        Route::post('/payments', [PaymentController::class, 'processPayment'])
            ->middleware('role:admin,operator');
        Route::get('/payments', [PaymentController::class, 'getAllPayments'])
            ->middleware('role:admin,operator');
        Route::get('/payments/user', [PaymentController::class, 'getPayments'])
            ->middleware('role:admin,operator,irs_specialist');
        Route::get('/payments/{invoiceNumber}', [PaymentController::class, 'getPayment'])
            ->middleware('role:admin,operator,irs_specialist');
    });

    // Settings
    Route::prefix('settings')->group(function () {
        Route::get('languages', [SettingsController::class, 'getLanguages']);
        
        Route::middleware('role:admin')->group(function () {
            Route::get('', [SettingsController::class, 'getSettings']);
            Route::post('', [SettingsController::class, 'updateSettings']);
            Route::post('language', [SettingsController::class, 'updateLanguage']);
            Route::get('language', [SettingsController::class, 'getLanguage']);
        });
    });

   // Organizations
Route::prefix('organizations')->group(function () {
    Route::get('', [OrganizationController::class, 'getOrganizations'])
        ->middleware('role:admin,operator');
    Route::get('{id}', [OrganizationController::class, 'getOrganization'])
        ->middleware('role:admin,operator,irs_specialist');
    
    Route::middleware('role:admin')->group(function () {
        Route::post('', [OrganizationController::class, 'createOrganization']);
        Route::put('{id}', [OrganizationController::class, 'updateOrganization']);
        Route::delete('{id}', [OrganizationController::class, 'deleteOrganization']);
    });

    // ID Configurations - READ operations (admin, operator, irs_specialist)
    Route::get('/{orgId}/id-configs', [IdConfigController::class, 'index'])
        ->middleware('role:admin,operator,irs_specialist');
    Route::get('/{orgId}/id-configs/{idConfig}', [IdConfigController::class, 'show'])
        ->middleware('role:admin,operator,irs_specialist');
    
    // ID Configurations - WRITE operations (admin only)
    Route::middleware('role:admin')->group(function () {
        Route::post('/{orgId}/id-configs', [IdConfigController::class, 'store']);
        Route::put('/{orgId}/id-configs/{idConfig}', [IdConfigController::class, 'update']);
        Route::delete('/{orgId}/id-configs/{idConfig}', [IdConfigController::class, 'destroy']);
        Route::post('/{orgId}/id-configs/reorder', [IdConfigController::class, 'reorder']);
    });
    
    // Field types endpoint (all roles)
    Route::get('/id-configs/field-types', [IdConfigController::class, 'getFieldTypes'])
        ->middleware('role:admin,operator,irs_specialist');
});
    // Services
    Route::prefix('services')->group(function () {
        // Public read-only endpoints
        Route::get('/fees', [ServiceFeesController::class, 'getServices']);
        Route::get('/fees/{id}', [ServiceFeesController::class, 'getServiceFee']);
        Route::get('/organizations/{organizationId}/services', [ServiceFeesController::class, 'getOrganizationServices']);
        Route::get('taxes', [ServiceTaxesController::class, 'getServiceTaxes']);
        Route::get('taxes/{id}', [ServiceTaxesController::class, 'getServiceTax']);

        // Admin/Operator management
        Route::middleware('role:admin,operator')->group(function () {
            // Fees
            Route::post('fees', [ServiceFeesController::class, 'createServiceFee']);
            Route::put('fees/{id}', [ServiceFeesController::class, 'updateServiceFee']);
            
            // Taxes
            Route::post('taxes', [ServiceTaxesController::class, 'createServiceTax']);
            Route::put('taxes/{id}', [ServiceTaxesController::class, 'updateServiceTax']);
        });
        //Admin-only management
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
        });

        // User-specific routes
        Route::get('/user/my-notifications', [NotificationController::class, 'getUserNotifications']);
        Route::post('/user/{notificationId}/mark-read', [NotificationController::class, 'markAsRead']);
        
        // Notification tokens and preferences
        Route::post('/user/tokens', [NotificationController::class, 'updateTokens']);
        Route::post('/user/preferences', [NotificationController::class, 'updatePreferences']);
        
        // System routes (admin only)
        Route::post('/process-scheduled', [NotificationController::class, 'processScheduled'])
            ->middleware('role:admin');
    });
    
    // States
    Route::prefix('states')->group(function () {
        Route::get('/', [StateController::class, 'index']);
        Route::get('/{id}', [StateController::class, 'show']);
        
        Route::middleware('role:admin, operator')->group(function () {
            Route::post('/', [StateController::class, 'store']);
            Route::put('/{id}', [StateController::class, 'update']);
        });
        
        Route::middleware('role:admin')->group(function () {
            Route::delete('/{id}', [StateController::class, 'destroy']);
        });
    });
    
    // IRS
    Route::prefix('irs')->group(function () {
        Route::get('/', [InternalRevenueServiceController::class, 'index']);
        
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
}); 
// Platforms routes
Route::prefix('platforms')->group(function () {
    Route::get('states', [SettingsController::class, 'getStates']);
    
    // Protected routes (require authentication)
    Route::middleware(['auth:api'])->group(function () {
        // Upload file
        Route::post('media/upload', [FileUploadController::class, 'upload']);
        
        // Delete file
        Route::delete('media/delete', [FileUploadController::class, 'delete']);
        
        // List files
        Route::get('media/list', [FileUploadController::class, 'list']);
        
        // Get file details
        Route::get('media/details', [FileUploadController::class, 'details']);
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

// User Management Routes - Protected by auth:api middleware
Route::prefix('roles')->group(function(){

    Route::middleware(['auth:api'])->group(function () {
        
        // User CRUD operations
        Route::get('/users', [UserManagementController::class, 'index']);
        
        
        Route::post('/users', [UserManagementController::class, 'store']) ;
        
        Route::put('/users/{id}', [UserManagementController::class, 'update']);
        
        Route::patch('/users/{id}/status', [UserManagementController::class, 'updateStatus']);
        
        Route::delete('/users/{id}', [UserManagementController::class, 'destroy']);

        // Helper routes for form data
        Route::get('/users/states', [UserManagementController::class, 'getStates']);
        
        Route::get('/', [UserManagementController::class, 'getRoles']);
    });
});