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

    public function __construct(Request $request)
    {
        $this->request = $request;
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

    public function apply(): Builder
    {
        $this->applySorting();
        $this->applyFiltering();

        return $this->query;
    }

    protected function applySorting(): void
    {
        $fields = explode(',', $this->request->get('sortFields', ''));
        $orders = explode(',', $this->request->get('sortOrders', ''));

        foreach ($fields as $index => $field) {
            if (! in_array($field, $this->allowedSorts)) {
                continue;
            }

            $direction = $orders[$index] ?? 'asc';
            $parts = explode('.', $field);

            if (count($parts) === 1) {
                $this->query->orderBy($field, $direction);
            } else {
                $relation = $parts[0];
                $column = $parts[1];

                $this->query->whereHas($relation, function ($q) use ($column, $direction) {
                    $q->orderBy($column, $direction);
                });
            }
        }
    }

    protected function applyFiltering(): void
    {
        foreach ($this->request->query() as $key => $value) {
            if (! str_starts_with($key, 'filter_')) {
                continue;
            }

            // match filter_field_identifier
            if (! preg_match('/^filter_(.*?)_(\d+)$/', $key, $matches)) {
                continue;
            }

            $fullField = $matches[1];
            $id = $matches[2];
            $modeKey = "filter_{$fullField}_{$id}_mode";
            $mode = $this->request->get($modeKey, 'equals');

            if (! in_array($fullField, $this->allowedFilters)) {
                continue;
            }

            $parts = explode('.', $fullField);

            // Handle 'in' and 'dateIs' specially
            if ($mode === 'in') {
                $values = explode(',', $value);
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
                // compare date part only
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

            // default operators
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

    protected function getOperatorFromMode(string $mode): string
    {
        return match ($mode) {
            'contains', 'starts_with', 'ends_with' => 'LIKE',
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
            'contains'    => "%{$value}%",
            'starts_with' => "{$value}%",
            'ends_with'   => "%{$value}",
            default       => $value,
        };
    }
}
