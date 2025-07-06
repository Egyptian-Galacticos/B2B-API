<?php

namespace App\Services\Admin;

use App\Models\Company;
use App\Models\User;
use App\Services\QueryHandler;
use Exception;
use Illuminate\Contracts\Pagination\LengthAwarePaginator;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Http\Request;

class UserService
{
    /**
     * Get all users with filtering and pagination.
     *
     * @param int|null $excludeUserId User ID to exclude from results
     */
    public function getAllUsersWithFilters(Request $request, ?int $excludeUserId = null): LengthAwarePaginator
    {
        $query = User::with(['roles', 'company']);

        if ($excludeUserId) {
            $query->where('id', '!=', $excludeUserId);
        }

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
                'last_login_at',
            ]);

        // temp
        $this->applyCustomFilters($query, $request);

        $filteredQuery = $queryHandler->apply();

        return $filteredQuery->paginate($request->per_page ?? 15);
    }

    /**
     * Apply custom filters for user queries.
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

        if ($request->has('is_email_verified') && $request->is_email_verified !== null) {
            $isVerified = in_array($request->is_email_verified, [true, 1, '1', 'true', 'TRUE'], true);
            $query->where('is_email_verified', $isVerified);
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
     * @throws Exception
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

        if ($user->status === 'pending') {
            throw new Exception('Cannot modify pending user status. Use seller registration review instead.');
        }

        $user->update([
            'status'     => $data['status'],
            'updated_at' => now(),
        ]);

        if ($data['status'] === 'suspended' && $user->hasRole('seller')) {
            $user->removeRole('seller');
        }

        return $user->fresh(['roles', 'company']);
    }

    /**
     * Get user details with related data.
     */
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

    /**
     * Perform bulk user actions.
     *
     * @throws Exception
     */
    public function bulkUserAction(array $userIds, string $action, int $adminId, ?string $reason = null): array
    {
        $successful = [];

        $users = User::whereIn('id', $userIds)
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            })
            ->where('id', '!=', $adminId)
            ->where('status', '!=', 'pending')
            ->with('roles')
            ->get();

        if ($users->isEmpty()) {
            throw new Exception('No users found for the provided IDs or all users are admins/pending.');
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
     * Suspend a user and remove seller role if applicable.
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

    /**
     * Get pending seller registrations.
     */
    public function getPendingSellerRegistrations(): Collection
    {
        return User::with(['roles', 'company'])
            ->where('status', 'pending')
            ->whereHas('company')
            ->whereDoesntHave('roles', function ($q) {
                $q->where('name', 'admin');
            })
            ->get();
    }

    /**
     * Review seller registration (approve or reject).
     */
    public function reviewSellerRegistration(int $companyId, string $action, ?string $reason = null, ?string $notes = null, ?int $adminId = null): array
    {
        $company = Company::with('user')->findOrFail($companyId);

        if (! $company->user) {
            throw new Exception('Company has no associated user');
        }

        if ($company->user->hasRole('admin')) {
            throw new Exception('Cannot review admin user company');
        }

        if ($company->user->status !== 'pending') {
            throw new Exception('User is not in pending status for seller registration');
        }

        switch ($action) {
            case 'approve':
                return $this->approveSellerRegistration($company, $reason, $notes, $adminId);
            case 'reject':
                return $this->rejectSellerRegistration($company, $reason, $notes, $adminId);
            default:
                throw new Exception('Invalid action');
        }
    }

    /**
     * Approve seller registration.
     */
    private function approveSellerRegistration(Company $company, ?string $reason, ?string $notes, ?int $adminId): array
    {
        $company->user->update(['status' => 'active']);

        if (! $company->user->hasRole('seller')) {
            $company->user->assignRole('seller');
        }

        return [
            'action'       => 'approved',
            'company_id'   => $company->id,
            'company_name' => $company->name,
            'user_id'      => $company->user->id,
            'user_email'   => $company->user->email,
            'reason'       => $reason,
            'notes'        => $notes,
            'reviewed_at'  => now(),
            'reviewed_by'  => $adminId,
        ];
    }

    private function rejectSellerRegistration(Company $company, ?string $reason, ?string $notes, ?int $adminId): array
    {
        $company->user->update(['status' => 'active']);

        if ($company->user->hasRole('seller')) {
            $company->user->removeRole('seller');
        }

        if (! $company->user->hasRole('buyer')) {
            $company->user->assignRole('buyer');
        }

        return [
            'action'       => 'rejected',
            'company_id'   => $company->id,
            'company_name' => $company->name,
            'user_id'      => $company->user->id,
            'user_email'   => $company->user->email,
            'reason'       => $reason,
            'notes'        => $notes,
            'reviewed_at'  => now(),
            'reviewed_by'  => $adminId,
        ];
    }
}
