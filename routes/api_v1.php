<?php

use App\Http\Controllers\Api\v1\AiSearchController;
use App\Http\Controllers\Api\v1\AuthController;
use App\Http\Controllers\Api\v1\BroadcastingController;

use App\Http\Controllers\Api\v1\CategoryController;
use App\Http\Controllers\Api\v1\ChatController;
use App\Http\Controllers\Api\v1\CompanyController;
use App\Http\Controllers\Api\v1\EmailVerificationController;
use App\Http\Controllers\Api\v1\NotificationController;
use App\Http\Controllers\Api\v1\ProductController;
use App\Http\Controllers\Api\v1\SellerUpgradeController;
use App\Http\Controllers\Api\v1\StatisticsController;

use App\Http\Controllers\Api\v1\TagController;
use App\Http\Controllers\Api\v1\UserController;
use App\Http\Controllers\Api\v1\WishlistController;
use Illuminate\Support\Facades\Route;

Route::prefix('v1')->group(function () {

    // ========================================
    // PUBLIC ROUTES (No Authentication Required)
    // ========================================

    // Broadcasting authentication route (needs to be outside JWT auth group)
    Route::post('/broadcasting/auth', [BroadcastingController::class, 'authenticate'])
        ->middleware(['broadcasting.auth'])
        ->name('broadcasting.auth');

    Route::prefix('auth')->group(function () {
        // Authentication endpoints
        Route::post('login', [AuthController::class, 'login'])->name('auth.login');
        Route::post('register', [AuthController::class, 'register'])->name('auth.register');
        Route::post('refresh-token', [AuthController::class, 'refresh'])->name('auth.refresh');

        // Password reset endpoints
        Route::post('forgot-password', [AuthController::class, 'sendResetLink'])->name('auth.forgot-password');
        Route::post('reset-password', [AuthController::class, 'resetPassword'])->name('auth.reset-password');

        // Email verification (token-based, no auth required)
        Route::post('email/verify', [EmailVerificationController::class, 'verify'])->name('auth.email.verify');
    });

    // Public products endpoint (browsing without auth)
    Route::get('products', [ProductController::class, 'index'])->name('products.public.index');
    Route::get('products/{slug}', [ProductController::class, 'show'])->name('products.public.show');
    Route::get('products/tags/all', [TagController::class, 'index'])->name('products.tags.index');
    Route::post('products/tags/clear-cache', [TagController::class, 'clearCache'])->name('products.tags.clear-cache');
    Route::get('products/ai/search', [AiSearchController::class, 'index'])->name('products.ai-search');

    // Public category endpoints (browsing without auth)
    Route::get('categories', [CategoryController::class, 'index'])->name('categories.public.index');
    Route::get('categories/{category}', [CategoryController::class, 'show'])
        ->whereNumber('category')
        ->name('categories.public.show');

    // ========================================
    // PROTECTED ROUTES (Authentication Required)
    // ========================================

    // NOTIFICATION ROUTES
    Route::prefix('notifications')->middleware(['jwt.auth'])->group(function () {
        Route::get('/', [NotificationController::class, 'index'])->name('notifications.index');
        Route::get('/unread', [NotificationController::class, 'unread'])->name('notifications.unread');
        Route::patch('/mark-all-as-read', [NotificationController::class, 'markAllAsRead'])->name('notifications.mark-all-as-read');
        Route::patch('/{id}/mark-as-read', [NotificationController::class, 'markAsRead'])->name('notifications.mark-as-read');
        Route::patch('/{id}/mark-as-unread', [NotificationController::class, 'markAsUnread'])->name('notifications.mark-as-unread');
    });

    Route::middleware(['jwt.auth'])->group(function () {

        // =====================================
        // BASIC AUTH ROUTES (Just Authentication)
        // =====================================
        Route::prefix('auth')->group(function () {
            Route::post('logout', [AuthController::class, 'logout'])->name('auth.logout');
            // Email verification management (requires auth but not verified email)
            Route::prefix('email')->group(function () {
                Route::post('send-verification', [EmailVerificationController::class, 'send'])->name('email.send');
                Route::post('resend-verification', [EmailVerificationController::class, 'resend'])->name('email.resend');
                Route::get('status', [EmailVerificationController::class, 'status'])->name('email.status');
            });

            // Company email verification management
            Route::prefix('company-email')->group(function () {
                Route::post('send-verification', [EmailVerificationController::class, 'sendCompany'])->name('company-email.send');
                Route::post('resend-verification', [EmailVerificationController::class, 'resendCompany'])->name('company-email.resend');
            });
        });

        // =====================================================
        // BASIC USER ROUTES (No Need for Email Verification)
        // =====================================================
        Route::get('me', [AuthController::class, 'me'])->name('auth.me');
        Route::prefix('user')->group(function () {
            Route::put('profile', [UserController::class, 'updateProfile'])->name('users.profile.update');
            Route::put('password', [UserController::class, 'updatePassword'])->name('users.password.update');
            Route::put('company', [CompanyController::class, 'update'])->name('company.update');
            Route::prefix('wishlist')->group(function () {
                Route::get('/', [WishlistController::class, 'index'])->name('wishlist.index');
                Route::post('/', [WishlistController::class, 'store'])->name('wishlist.store');
                Route::delete('/{product:id}', [WishlistController::class, 'destroy'])->name('wishlist.destroy');
                Route::post('/check', [WishlistController::class, 'check'])->name('wishlist.check');
                Route::post('/clear', [WishlistController::class, 'clear'])->name('wishlist.clear');
                Route::get('/summary', [WishlistController::class, 'summary'])->name('wishlist.summary');
            });
            Route::delete('{user}', [UserController::class, 'destroy'])->name('users.destroy');
        });

        // Company management
        Route::post('seller/upgrade', [SellerUpgradeController::class, 'upgradeToSeller'])->name('seller.upgrade');

        // ====================================================
        // VERIFIED & ACTIVE USER ROUTES (Full Restrictions)
        // ====================================================
        Route::middleware(['is_email_verified', 'is_suspended'])->group(function () {
            Route::get('statistics', StatisticsController::class)->name('statistics');

            // category management (requires ownership)
            Route::apiResource('categories', CategoryController::class)->except(['index', 'show']);

            // Additional category routes for soft delete management
            Route::prefix('categories')->group(function () {
                Route::get('trashed', [CategoryController::class, 'trashed'])->name('categories.trashed');
                Route::patch('{id}/restore', [CategoryController::class, 'restore'])->name('categories.restore');
                Route::delete('{id}/force', [CategoryController::class, 'forceDelete'])->name('categories.force-delete');
            });

            // Product creation (no ownership check needed)
            Route::post('products', [ProductController::class, 'store'])->name('products.store');
            Route::post('products/bulk-import', [ProductController::class, 'bulkImport'])->name('products.bulk.import');
            Route::get('seller/products', [ProductController::class, 'sellerProducts'])->name('products.seller.index');
            // Product Bulk Actions (ownership verified in controller)
            Route::post('products/bulk-delete', [ProductController::class, 'bulkDelete'])->name('products.bulk.delete');
            Route::post('products/bulk-active', [ProductController::class, 'bulkActive'])->name('products.bulk.active');
            Route::post('products/bulk-deactivate', [ProductController::class, 'bulkDeactivate'])->name('products.bulk.deactivate');
            // Product management (requires ownership)
            Route::middleware(['product.owner'])->group(function () {
                Route::put('products/{product}', [ProductController::class, 'update'])->name('products.update');
                Route::delete('products/{product}', [ProductController::class, 'destroy'])->name('products.destroy');
                Route::patch('products/{product}', [ProductController::class, 'updateStatus'])->name('products.status');

                // Product media management routes
                Route::delete('products/{product}/media/{collection}/{mediaId}', [ProductController::class, 'deleteProductMedia'])->name('products.documents.destroy');
            });

            // User management
            Route::prefix('users')->group(function () {
                Route::patch('{user}/restore', [UserController::class, 'restore'])->name('users.restore');
                Route::delete('{user}/force-delete', [UserController::class, 'forceDelete'])->name('users.force-delete');
            });

            // =====================================
            // ROLE-BASED ROUTES (Add when needed)
            // =====================================
            /*
            // Admin only routes
            Route::middleware(['role:admin'])->group(function () {
                Route::get('admin/dashboard', [AdminController::class, 'dashboard']);
                Route::resource('admin/users', AdminUserController::class);
            });

            // Seller only routes
            Route::middleware(['role:seller'])->group(function () {
                Route::get('seller/dashboard', [SellerController::class, 'dashboard']);
                Route::resource('seller/inventory', InventoryController::class);
            });

            // Buyer only routes
            Route::middleware(['role:buyer'])->group(function () {
                Route::resource('orders', OrderController::class);
                Route::resource('cart', CartController::class);
            });
            */

            // CHAT ROUTES
            // =====================================
            Route::prefix('chat')->group(function () {
                // Get user's conversations
                Route::get('conversations', [ChatController::class, 'conversations'])->name('chat.conversations');

                // Start a new conversation
                Route::post('conversations', [ChatController::class, 'startConversation'])->name('chat.conversations.start');

                // Search conversations
                Route::get('conversations/search', [ChatController::class, 'searchConversations'])->name('chat.conversations.search');

                // Get unread message count
                Route::get('unread-count', [ChatController::class, 'unreadCount'])->name('chat.unread.count');

                // Conversation-specific routes
                Route::prefix('conversations/{conversationId}')->group(function () {
                    // Get messages for a conversation
                    Route::get('messages', [ChatController::class, 'messages'])->name('chat.messages');

                    // Send a message
                    Route::post('messages', [ChatController::class, 'sendMessage'])->name('chat.messages.send');

                    // Mark messages as read
                    Route::patch('read', [ChatController::class, 'markConversationAsRead'])->name('chat.conversation.read');
                });

                // Message-specific routes
                Route::prefix('messages/{messageId}')->group(function () {
                    // Mark a specific message as read
                    Route::patch('read', [ChatController::class, 'markMessageAsRead'])->name('chat.message.read');
                });
            });
        });
    });
    require __DIR__.'/rfq.php';
    require __DIR__.'/admin.php';
});
