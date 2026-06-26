<?php

use Illuminate\Support\Facades\Route;
use App\Http\Controllers\Api\{
    AuthController,
    DashboardController,
    ItemController,
    CategoryController,
    SupplierController,
    TransactionController,
    NotificationController,
    ReportController
};

/*
|--------------------------------------------------------------------------
| PUBLIC ROUTES
|--------------------------------------------------------------------------
*/
Route::post('/login', [AuthController::class, 'login']);
Route::post('/register', [AuthController::class, 'register']);
Route::post('/forgot-password', [AuthController::class, 'forgotPassword']);

/*
|--------------------------------------------------------------------------
| PROTECTED ROUTES
|--------------------------------------------------------------------------
*/
Route::middleware('auth:sanctum')->group(function () {

    // Auth
    Route::post('/logout', [AuthController::class, 'logout']);
    Route::get('/me', [AuthController::class, 'me']);
    Route::post('/profile', [AuthController::class, 'updateProfile']);
    Route::post('/change-password', [AuthController::class, 'changePassword']);

    // Dashboard
    Route::get('/dashboard', [DashboardController::class, 'index']);

    // Items
    Route::get('/items', [ItemController::class, 'index']);
    Route::post('/items', [ItemController::class, 'store']);
    Route::get('/items/{id}', [ItemController::class, 'show']);
    Route::post('/items/{id}', [ItemController::class, 'update']);
    Route::delete('/items/{id}', [ItemController::class, 'destroy']);
    Route::post('/items/{id}/adjust', [ItemController::class, 'adjustStock']);

    // Categories
    Route::get('/categories', [CategoryController::class, 'index']);
    Route::post('/categories', [CategoryController::class, 'store']);
    Route::post('/categories/{id}', [CategoryController::class, 'update']);
    Route::delete('/categories/{id}', [CategoryController::class, 'destroy']);

    // Suppliers (Sinkron dengan SupplierController@update yang menerima Request + ID)
    Route::get('/suppliers', [SupplierController::class, 'index']);
    Route::post('/suppliers', [SupplierController::class, 'store']);
    Route::get('/suppliers/{id}', [SupplierController::class, 'show']);
    Route::post('/suppliers/{id}', [SupplierController::class, 'update']); 
    Route::delete('/suppliers/{id}', [SupplierController::class, 'destroy']);

    // Transactions
    Route::get('/incoming', [TransactionController::class, 'incomingIndex']);
    Route::post('/incoming', [TransactionController::class, 'incomingStore']);
    Route::get('/outgoing', [TransactionController::class, 'outgoingIndex']);
    Route::post('/outgoing', [TransactionController::class, 'outgoingStore']);

    // Notifications
    Route::get('/notifications', [NotificationController::class, 'index']);
    Route::get('/notifications/unread-count', [NotificationController::class, 'unreadCount']);
    Route::post('/notifications/read', [NotificationController::class, 'markRead']);
    Route::post('/notifications/read-all', [NotificationController::class, 'markAllRead']);

    // Reports
    Route::get('/reports/stock', [ReportController::class, 'stock']);
    Route::get('/reports/incoming', [ReportController::class, 'incoming']);
    Route::get('/reports/outgoing', [ReportController::class, 'outgoing']);
    Route::get('/reports/movements', [ReportController::class, 'movements']);
});