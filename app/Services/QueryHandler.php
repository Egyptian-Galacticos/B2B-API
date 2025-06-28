<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Database\Eloquent\Relations\Relation;
use Illuminate\Http\Request;
use Illuminate\Support\Str;

class QueryHandler
{
    protected Request $request;
    protected Builder $query;
    protected array $allowedSorts = [];
    protected array $allowedFilters = [];
    protected array $searchableFields = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
    }

    public function setBaseQuery(Builder $query): self
    {
        $this->query = $query;

        return $this;
    }

    protected function normalizeFields(array $fields): array
    {
        $baseModel = $this->query->getModel();

        return collect($fields)->mapWithKeys(function ($item) use ($baseModel) {
            $field = is_array($item) ? $item['field'] : $item;
            $segments = explode('_', $field);
            $model = $baseModel;
            $relationParts = [];

            while (! empty($segments)) {
                $next = $segments[0];

                if (! method_exists($model, $next)) {
                    break;
                }

                try {
                    $relation = $model->{$next}();

                    if ($relation instanceof Relation) {
                        $relationParts[] = array_shift($segments);
                        $model = $relation->getRelated();
                    } else {
                        break;
                    }
                } catch (\Throwable $e) {
                    break;
                }
            }

            $fullField = implode('.', array_merge($relationParts, $segments));

            return [Str::lower($fullField) => ['field' => $fullField]];
        })->toArray();
    }

    public function setAllowedSorts(array $fields): self
    {
        $this->allowedSorts = $this->normalizeFields($fields);

        return $this;
    }

    public function setAllowedFilters(array $fields): self
    {
        $this->allowedFilters = $this->normalizeFields($fields);

        return $this;
    }

    public function setSearchableFields(array $fields): self
    {
        $this->searchableFields = $fields;

        return $this;
    }

    public function apply(): Builder
    {
        return $this->applyGlobalSearch()
            ->applySorting()
            ->applyFiltering()
            ->query;
    }

    protected function applyGlobalSearch(): self
    {
        $searchTerm = strtolower($this->request->get('search', ''));

        if (! $searchTerm || empty($this->searchableFields)) {
            return $this;
        }

        $this->query->where(function (Builder $query) use ($searchTerm) {
            foreach ($this->searchableFields as $field) {
                $this->applyCondition($query, $field, 'LIKE', "%{$searchTerm}%", true);
            }
        });

        return $this;
    }

    protected function applySorting(): self
    {
        $fields = explode(',', $this->request->get('sortFields', ''));
        $orders = explode(',', $this->request->get('sortOrders', ''));

        foreach ($fields as $index => $field) {
            $key = Str::lower($field);
            if (! isset($this->allowedSorts[$key])) {
                continue;
            }

            $direction = strtolower($orders[$index] ?? 'asc');
            $this->applyOrder($field, $direction);
        }

        return $this;
    }

    protected function applyFiltering(): self
    {
        foreach ($this->request->query() as $key => $value) {
            if (! str_starts_with($key, 'filter_')) {
                continue;
            }

            if (! preg_match('/^filter_([a-zA-Z0-9_]+)_(\d+)$/', $key, $matches)) {
                continue;
            }

            $rawField = $matches[1]; // e.g. category_name or is_featured
            $id = $matches[2];

            // Safely resolve relationships
            $segments = explode('_', $rawField);
            $model = $this->query->getModel();
            $relationParts = [];

            while (! empty($segments)) {
                $next = $segments[0];

                if (method_exists($model, $next)) {
                    $relationParts[] = array_shift($segments);
                    $model = $model->{$next}()->getRelated();
                } else {
                    break;
                }
            }

            $fullField = implode('.', array_merge($relationParts, $segments));
            $lookupField = Str::lower($fullField);

            if (! isset($this->allowedFilters[$lookupField])) {
                continue;
            }

            $mode = $this->request->get("filter_{$rawField}_{$id}_mode", 'equals');
            $operator = $this->getOperatorFromMode($mode);
            $formattedValue = $this->formatValueByMode(strtolower($value), $mode);

            $this->applyCondition($this->query, $fullField, $operator, $formattedValue);
        }

        return $this;
    }

    protected function applyCondition(Builder $query, string $field, string $operator, string $value, bool $or = false): void
    {
        $parts = explode('.', $field);
        $column = array_pop($parts);

        if (empty($parts)) {
            $method = $or ? 'orWhereRaw' : 'whereRaw';
            $query->{$method}("LOWER({$column}) {$operator} ?", [$value]);
        } else {
            $relationPath = implode('.', array_map('lcfirst', $parts));
            $method = $or ? 'orWhereHas' : 'whereHas';
            $query->{$method}($relationPath, function ($q) use ($column, $operator, $value) {
                $q->whereRaw("LOWER({$column}) {$operator} ?", [$value]);
            });
        }
    }

    protected function applyOrder(string $field, string $direction): void
    {
        $parts = explode('.', $field);
        $column = array_pop($parts);

        if (empty($parts)) {
            $this->query->orderByRaw("LOWER({$column}) {$direction}");
        } else {
            $relationPath = implode('.', array_map('lcfirst', $parts));
            $this->query->whereHas($relationPath, function ($q) use ($column, $direction) {
                $q->orderByRaw("LOWER({$column}) {$direction}");
            });
        }
    }

    protected function getOperatorFromMode(string $mode): string
    {
        return match ($mode) {
            'contains', 'startsWith', 'endsWith' => 'LIKE',
            'equals'     => '=',
            'not_equals' => '!=',
            'gte'        => '>=',
            'lte'        => '<=',
            default      => '=',
        };
    }

    protected function formatValueByMode(string $value, string $mode): string
    {
        return match ($mode) {
            'contains'   => "%{$value}%",
            'startsWith' => "{$value}%",
            'endsWith'   => "%{$value}",
            default      => $value,
        };
    }
}

// namespace App\Services;

// use Illuminate\Database\Eloquent\Builder;
// use Illuminate\Http\Request;
// use Illuminate\Support\Str;

// class QueryHandler
// {
//     protected Request $request;
//     protected Builder $query;
//     protected array $allowedSorts = [];
//     protected array $allowedFilters = [];
//     protected array $searchableFields = [];

//     public function __construct(Request $request)
//     {
//         $this->request = $request;
//     }

//     public function setBaseQuery(Builder $query): self
//     {
//         $this->query = $query;
//         return $this;
//     }

//     protected function normalizeFields(array $fields): array
//     {
//         return collect($fields)
//             ->mapWithKeys(function ($item) {
//                 $field = is_array($item) ? $item['field'] : $item;
//                 return [Str::lower($field) => ['field' => $field]];
//             })
//             ->toArray();
//     }

//     public function setAllowedSorts(array $fields): self
//     {
//         $this->allowedSorts = $this->normalizeFields($fields);
//         return $this;
//     }

//     public function setAllowedFilters(array $fields): self
//     {
//         $this->allowedFilters = $this->normalizeFields($fields);
//         return $this;
//     }

//     public function setSearchableFields(array $fields): self
//     {
//         $this->searchableFields = $fields;
//         return $this;
//     }

//     public function apply(): Builder
//     {
//         return $this->applyGlobalSearch()
//                     ->applySorting()
//                     ->applyFiltering()
//                     ->query;
//     }

//     protected function applyGlobalSearch(): self
//     {
//         $searchTerm = strtolower($this->request->get('search', ''));

//         if (!$searchTerm || empty($this->searchableFields)) {
//             return $this;
//         }

//         $this->query->where(function (Builder $query) use ($searchTerm) {
//             foreach ($this->searchableFields as $field) {
//                 $this->applyCondition($query, $field, 'LIKE', "%{$searchTerm}%", true);
//             }
//         });

//         return $this;
//     }

//     protected function applySorting(): self
//     {
//         $fields = explode(',', $this->request->get('sortFields', ''));
//         $orders = explode(',', $this->request->get('sortOrders', ''));

//         foreach ($fields as $index => $field) {
//             $key = Str::lower($field);
//             if (!isset($this->allowedSorts[$key])) continue;

//             $direction = strtolower($orders[$index] ?? 'asc');
//             $this->applyOrder($field, $direction);
//         }

//         return $this;
//     }

//     protected function applyFiltering(): self
//     {
//         foreach ($this->request->query() as $key => $value) {
//             if (!str_starts_with($key, 'filter_') || !preg_match('/^filter_(.*?)_(\d+)$/', $key, $matches)) continue;

//             $fullField = $matches[1];
//             $mode = $this->request->get("filter_{$fullField}_{$matches[2]}_mode", 'equals');

//             $lookupField = Str::lower($fullField);
//             if (!isset($this->allowedFilters[$lookupField])) continue;

//             $operator = $this->getOperatorFromMode($mode);
//             $formattedValue = $this->formatValueByMode(strtolower($value), $mode);

//             $this->applyCondition($this->query, $fullField, $operator, $formattedValue);
//         }

//         return $this;
//     }

//     protected function applyCondition(Builder $query, string $field, string $operator, string $value, bool $or = false): void
//     {
//         $parts = explode('.', $field);
//         $column = array_pop($parts);

//         if (empty($parts)) {
//             $method = $or ? 'orWhereRaw' : 'whereRaw';
//             $query->{$method}("LOWER({$column}) {$operator} ?", [$value]);
//         } else {
//             $relationPath = implode('.', $parts);
//             $method = $or ? 'orWhereHas' : 'whereHas';
//             $query->{$method}($relationPath, function ($q) use ($column, $operator, $value) {
//                 $q->whereRaw("LOWER({$column}) {$operator} ?", [$value]);
//             });
//         }
//     }

//     protected function applyOrder(string $field, string $direction): void
//     {
//         $parts = explode('.', $field);
//         $column = array_pop($parts);

//         if (empty($parts)) {
//             $this->query->orderByRaw("LOWER({$column}) {$direction}");
//         } else {
//             $relationPath = implode('.', $parts);
//             $this->query->whereHas($relationPath, function ($q) use ($column, $direction) {
//                 $q->orderByRaw("LOWER({$column}) {$direction}");
//             });
//         }
//     }

//     protected function getOperatorFromMode(string $mode): string
//     {
//         return match ($mode) {
//             'contains', 'startsWith', 'endsWith' => 'LIKE',
//             'equals' => '=',
//             'not_equals' => '!=',
//             'gte' => '>=',
//             'lte' => '<=',
//             default => '=',
//         };
//     }

//     protected function formatValueByMode(string $value, string $mode): string
//     {
//         return match ($mode) {
//             'contains' => "%{$value}%",
//             'startsWith' => "{$value}%",
//             'endsWith' => "%{$value}",
//             default => $value,
//         };
//     }
// }
