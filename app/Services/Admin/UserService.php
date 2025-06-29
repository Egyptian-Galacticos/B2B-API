<?php

namespace App\Services\Admin;

use App\Models\User;
use App\Services\QueryHandler;
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
}
