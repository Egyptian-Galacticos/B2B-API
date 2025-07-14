<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\NotificationResource;
use App\Traits\ApiResponse;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;

class NotificationController extends Controller
{
    use ApiResponse;

    /**
     * Get all notifications for the authenticated user.
     */
    public function index(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return $this->apiResponseErrors('Unauthenticated.', [], 401);
            }

            $notifications = $user->notifications;

            return $this->apiResponse(NotificationResource::collection($notifications), 'Notifications retrieved successfully.');
        } catch (\Exception $e) {
            return $this->apiResponseErrors('Failed to retrieve notifications.', [$e->getMessage()], 500);
        }
    }

    /**
     * Get unread notifications only for the authenticated user.
     */
    public function unread(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return $this->apiResponseErrors('Unauthenticated.', [], 401);
            }

            $unreadNotifications = $user->unreadNotifications;

            return $this->apiResponse(NotificationResource::collection($unreadNotifications), 'Unread notifications retrieved successfully.');
        } catch (\Exception $e) {
            return $this->apiResponseErrors('Failed to retrieve unread notifications.', [$e->getMessage()], 500);
        }
    }

    /**
     * Mark all unread notifications as read for the authenticated user.
     */
    public function markAllAsRead(Request $request): JsonResponse
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return $this->apiResponseErrors('Unauthenticated.', [], 401);
            }

            $user->unreadNotifications->markAsRead();

            return $this->apiResponse(null, 'All unread notifications marked as read.');
        } catch (\Exception $e) {
            return $this->apiResponseErrors('Failed to mark all notifications as read.', [$e->getMessage()], 500);
        }
    }

    /**
     * Mark a single notification as read.
     */
    public function markAsRead(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return $this->apiResponseErrors('Unauthenticated.', [], 401);
            }

            $notification = $user->notifications()->where('id', $id)->first();

            if (! $notification) {
                return $this->apiResponseErrors('Notification not found or does not belong to user.', [], 404);
            }

            if ($notification->read_at === null) {
                $notification->markAsRead();

                return $this->apiResponse(null, 'Notification marked as read.');
            }

            return $this->apiResponse(null, 'Notification already marked as read.');
        } catch (\Exception $e) {
            return $this->apiResponseErrors('Failed to mark notification as read.', [$e->getMessage()], 500);
        }
    }

    /**
     * Mark a single notification as unread.
     */
    public function markAsUnread(string $id): JsonResponse
    {
        try {
            $user = Auth::user();
            if (! $user) {
                return $this->apiResponseErrors('Unauthenticated.', [], 401);
            }

            $notification = $user->notifications()->where('id', $id)->first();

            if (! $notification) {
                return $this->apiResponseErrors('Notification not found or does not belong to user.', [], 404);
            }

            if ($notification->read_at !== null) {
                $notification->markAsUnread();

                return $this->apiResponse(null, 'Notification marked as unread.');
            }

            return $this->apiResponse(null, 'Notification already marked as unread.');
        } catch (\Exception $e) {
            return $this->apiResponseErrors('Failed to mark notification as unread.', [$e->getMessage()], 500);
        }
    }
}
