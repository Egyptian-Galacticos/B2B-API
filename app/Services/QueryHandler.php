<?php

namespace App\Services;

use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class QueryHandler
{
    protected Request $request;
    protected Builder $query;
    protected array $allowedSorts = [];
    protected array $allowedFilters = [];
    protected array $searchableFields = [];
    protected array $originalParams = [];

    public function __construct(Request $request)
    {
        $this->request = $request;
        $this->originalParams = $this->parseQueryManuallyWithDots();
    }

    protected function parseQueryManuallyWithDots(): array
    {
        $queryString = $_SERVER['QUERY_STRING'] ?? '';
        $params = [];

        if (empty($queryString)) {
            return $params;
        }

        $pairs = explode('&', $queryString);

        foreach ($pairs as $pair) {
            if (empty($pair)) {
                continue;
            }

            if (strpos($pair, '=') !== false) {
                $parts = explode('=', $pair, 2);
                $key = urldecode($parts[0]);
                $value = urldecode($parts[1]);
                $params[$key] = $value;
            } else {
                $params[urldecode($pair)] = '';
            }
        }

        return $params;
    }

    protected function getOriginalParam(string $key, $default = null)
    {
        return $this->originalParams[$key] ?? $default;
    }

    protected function getOriginalParams(): array
    {
        return $this->originalParams;
    }

    protected function hasOriginalParam(string $key): bool
    {
        return array_key_exists($key, $this->originalParams);
    }

    public function setBaseQuery(Builder $query): self
    {
        $this->query = $query;

        return $this;
    }

    public function setAllowedSorts(array $fields): self
    {
        $this->allowedSorts = $fields;

        return $this;
    }

    public function setAllowedFilters(array $fields): self
    {
        $this->allowedFilters = $fields;

        return $this;
    }

    public function setSearchableFields(array $fields): self
    {
        $this->searchableFields = $fields;

        return $this;
    }

    public function apply(): Builder
    {
        $this->applyGlobalSearch();
        $this->applySorting();
        $this->applyFiltering();

        return $this->query;
    }

    protected function applySorting(): void
    {

        $fields = explode(',', $this->getOriginalParam('sortFields', ''));
        $orders = explode(',', $this->getOriginalParam('sortOrders', ''));

        foreach ($fields as $index => $field) {
            $field = trim($field);
            if (empty($field)) {
                continue;
            }

            $direction = trim($orders[$index] ?? 'asc');
            $parts = explode('.', $field);

            if (count($parts) === 1) {
                if (! in_array($field, $this->allowedSorts)) {
                    continue;
                }
                $this->query->orderBy($field, $direction);
            } else {
                [$relation, $column] = $parts;

                if (! method_exists($this->query->getModel(), $relation)) {
                    continue;
                }

                $relationInstance = $this->query->getModel()->{$relation}();
                $relatedTable = $relationInstance->getRelated()->getTable();
                $alias = $relatedTable.'_sort';
                $foreignKey = $relationInstance->getQualifiedForeignKeyName();

                if (! collect($this->query->getQuery()->joins)->pluck('table')->contains($relatedTable.' as '.$alias)) {
                    $this->query->leftJoin("{$relatedTable} as {$alias}", $foreignKey, '=', "{$alias}.id");
                }

                $this->query->orderBy("{$alias}.{$column}", $direction);
            }
        }
    }

    protected function applyFiltering(): void
    {
        foreach ($this->originalParams as $key => $value) {
            if (! str_starts_with($key, 'filter_')) {
                continue;
            }

            if (! preg_match('/^filter_(.*?)_(\d+)$/', $key, $matches)) {
                continue;
            }

            $fullField = $matches[1];
            $id = $matches[2];
            $modeKey = "filter_{$fullField}_{$id}_mode";
            $mode = $this->getOriginalParam($modeKey, 'equals');

            if (! in_array($fullField, $this->allowedFilters)) {
                continue;
            }

            // 🔹 HANDLE VIRTUAL 'price' FILTER 🔹
            if ($fullField === 'price') {
                $operator = $this->getOperatorFromMode($mode);
                $formattedValue = $this->formatValueByMode($value, $mode);

                $this->query->whereHas('tiers', function ($q) use ($operator, $formattedValue) {
                    $q->where('price', $operator, $formattedValue);
                });

                continue;
            }

            $parts = explode('.', $fullField);

            if ($mode === 'in') {
                $values = array_map('trim', explode(',', $value));
                if (count($parts) === 1) {
                    $this->query->whereIn($fullField, $values);
                } else {
                    $relation = $parts[0];
                    $field = $parts[1];
                    $this->query->whereHas($relation, function ($q) use ($field, $values) {
                        $q->whereIn($field, $values);
                    });
                }

                continue;
            }

            if ($mode === 'dateIs') {
                if (count($parts) === 1) {
                    $this->query->whereDate($fullField, '=', $value);
                } else {
                    $relation = $parts[0];
                    $field = $parts[1];
                    $this->query->whereHas($relation, function ($q) use ($field, $value) {
                        $q->whereDate($field, '=', $value);
                    });
                }

                continue;
            }

            $operator = $this->getOperatorFromMode($mode);
            $formattedValue = $this->formatValueByMode($value, $mode);

            if (count($parts) === 1) {
                $this->query->where($fullField, $operator, $formattedValue);
            } else {
                $relation = $parts[0];
                $field = $parts[1];
                $this->query->whereHas($relation, function ($q) use ($field, $operator, $formattedValue) {
                    $q->where($field, $operator, $formattedValue);
                });
            }
        }
    }

    protected function applyGlobalSearch(): self
    {
        $searchTerm = trim($this->request->get('search', ''));

        if (! $searchTerm || empty($this->searchableFields)) {
            return $this;
        }

        $mainTable = $this->query->getModel()->getTable();

        $this->query->where(function (Builder $query) use ($searchTerm, $mainTable) {
            foreach ($this->searchableFields as $field) {
                $parts = explode('.', $field);

                if (count($parts) === 1) {
                    // Example: products.name
                    $query->orWhere("{$mainTable}.{$field}", 'LIKE', "%{$searchTerm}%");
                } elseif (count($parts) === 2) {
                    // Example: category.name
                    [$relation, $column] = $parts;
                    $query->orWhereHas($relation, function ($q) use ($column, $searchTerm) {
                        $q->where($column, 'LIKE', "%{$searchTerm}%");
                    });
                }
            }
        });

        return $this;
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
