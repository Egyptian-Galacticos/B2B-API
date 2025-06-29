<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\QueryHandler;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Http\Request;

class UserService
{
    /**
     * Get all users with filtering and pagination using QueryHandler.
     *
     * @param int|null $excludeUserId User ID to exclude from results (e.g., current admin)
     */
    public function getAllUsersWithFilters(Request $request, ?int $excludeUserId = null): LengthAwarePaginator
    {
        $query = User::with(['roles', 'company']);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

        // Exclude admin users from the results
        $query->whereDoesntHave('roles', function ($q) {
            $q->where('name', 'admin');
        });

        $queryHandler = new QueryHandler($request);
        $queryHandler->setBaseQuery($query)
            ->setAllowedSorts([
                'first_name',
                'last_name',
                'email',
                'created_at',
                'last_login_at',
                'status',
            ])
            ->setAllowedFilters([
                'is_email_verified',
                'last_login_at',
            ]);

        // temp
        $this->applyCustomFilters($query, $request);

        $filteredQuery = $queryHandler->apply();

        return $filteredQuery->paginate($request->per_page ?? 15);
    }

    /**
     * Apply custom filters that QueryHandler doesn't handle.
     *
     * @param \Illuminate\Database\Eloquent\Builder $query
     */
    private function applyCustomFilters($query, Request $request): void
    {
        if ($request->filled('status')) {
            $query->where('status', $request->status);
        }

        if ($request->filled('role')) {
            $query->whereHas('roles', function ($q) use ($request) {
                $q->where('name', $request->role);
            });
        }

        if ($request->filled('registration_date_from')) {
            $query->whereDate('created_at', '>=', $request->registration_date_from);
        }

        if ($request->filled('registration_date_to')) {
            $query->whereDate('created_at', '<=', $request->registration_date_to);
        }

        if ($request->filled('search')) {
            $searchTerm = $request->search;
            $query->where(function ($q) use ($searchTerm) {
                $q->where('first_name', 'like', "%{$searchTerm}%")
                    ->orWhere('last_name', 'like', "%{$searchTerm}%")
                    ->orWhere('email', 'like', "%{$searchTerm}%")
                    ->orWhereRaw("CONCAT(first_name, ' ', last_name) like ?", ["%{$searchTerm}%"]);
            });
        }
    }

    /**
     * Update user status (suspend/activate).
     *
     * @throws \Exception
     */
    public function updateUserStatus(int $userId, array $data, int $adminId): User
    {
        $user = User::findOrFail($userId);

        if ($user->id === $adminId) {
            throw new Exception('You cannot modify your own account status');
        }

        // for future expansion adding admins to the system
        if ($user->hasRole('admin')) {
            throw new Exception('Cannot modify admin user status');
        }

        $user->update([
            'status'     => $data['status'],
            'updated_at' => now(),
        ]);

        if ($data['status'] === 'suspended' && $user->hasRole('seller')) {
            $user->removeRole('seller');
        }

        if ($data['status'] === 'active' && $user->company && ! $user->hasRole('seller')) {
            $user->assignRole('seller');
        }

        return $user->fresh(['roles', 'company']);
    }

    public function getUserDetails(int $userId): User
    {
        $user = User::with([
            'roles',
            'company',
            'products' => function ($query) {
                $query->select('id', 'seller_id', 'name', 'is_active', 'created_at')
                    ->limit(10);
            },
        ])
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            })
            ->findOrFail($userId);

        return $user;
    }

    public function bulkUserAction(array $userIds, string $action, int $adminId, ?string $reason = null): array
    {
        $successful = [];

        $users = User::whereIn('id', $userIds)
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            })
            ->where('id', '!=', $adminId)
            ->with('roles')
            ->get();
        if ($users->isEmpty()) {
            throw new Exception('No users found for the provided IDs or all users are admins.');
        }

        foreach ($users as $user) {
            switch ($action) {
                case 'suspend':
                    $this->suspendUser($user);
                    $successful[] = [
                        'id'     => $user->id,
                        'email'  => $user->email,
                        'action' => 'suspended',
                    ];
                    break;

                case 'activate':
                    $this->activateUser($user);
                    $successful[] = [
                        'id'     => $user->id,
                        'email'  => $user->email,
                        'action' => 'activated',
                    ];
                    break;

                case 'delete':
                    $this->softDeleteUser($user);
                    $successful[] = [
                        'id'     => $user->id,
                        'email'  => $user->email,
                        'action' => 'deleted',
                    ];
                    break;

                default:
                    throw new Exception('Invalid action');
            }
        }

        return ['successful' => $successful];
    }

    /**
     * Suspend a user.
     */
    private function suspendUser(User $user): void
    {
        if ($user->status !== 'suspended') {
            $user->update(['status' => 'suspended']);

            if ($user->hasRole('seller')) {
                $user->removeRole('seller');
            }
        }
    }

    /**
     * Activate a user.
     */
    private function activateUser(User $user): void
    {
        if ($user->status !== 'active') {
            $user->update(['status' => 'active']);

            if ($user->company && ! $user->hasRole('seller')) {
                $user->assignRole('seller');
            }
        }
    }

    /**
     * Soft delete a user.
     */
    private function softDeleteUser(User $user): void
    {
        if (! $user->trashed()) {
            $user->delete();
        }
    }
}
