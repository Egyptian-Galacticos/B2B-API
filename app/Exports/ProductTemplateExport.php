<?php

namespace App\Exports;

use App\Http\Requests\Product\StoreProductRequest;
use Maatwebsite\Excel\Concerns\FromArray;
use Maatwebsite\Excel\Concerns\WithColumnWidths;
use Maatwebsite\Excel\Concerns\WithHeadings;
use Maatwebsite\Excel\Concerns\WithStyles;
use PhpOffice\PhpSpreadsheet\Style\Fill;
use PhpOffice\PhpSpreadsheet\Worksheet\Worksheet;

class ProductTemplateExport implements FromArray, WithColumnWidths, WithHeadings, WithStyles
{
    protected array $fields;

    public function __construct()
    {
        $this->fields = $this->generateFieldsFromRequest();
    }

    public function headings(): array
    {
        return array_keys($this->fields);
    }

    public function array(): array
    {
        $descriptions = array_map(fn ($field) => $field['description'], $this->fields);

        $sampleData = array_map(fn ($field) => $field['sample'], $this->fields);

        $emptyRow = array_fill(0, count($this->fields), '');

        return [
            $descriptions,
            $sampleData,
            $emptyRow,
        ];
    }

    protected function generateFieldsFromRequest(): array
    {
        $request = new StoreProductRequest;
        $rules = $request->rules();

        $fields = [];

        foreach ($rules as $field => $fieldRules) {
            if (strpos($field, '.') !== false ||
                in_array($field, ['main_image', 'images', 'documents'])) {
                continue;
            }

            if ($field === 'category_id') {
                $fields['category_name'] = [
                    'sample'      => 'Electronics',
                    'description' => 'Category name ',
                ];

                continue;
            }

            if ($field === 'seller_id') {
                continue;
            }

            $fields[$field] = [
                'sample'      => $this->generateSampleValue($field, $fieldRules),
                'description' => $this->generateDescription($field, $fieldRules),
            ];
        }

        $tierFields = [
            'tier_1_min_qty' => [
                'sample'      => '1',
                'description' => 'Minimum quantity for tier 1 pricing',
            ],
            'tier_1_max_qty' => [
                'sample'      => '10',
                'description' => 'Maximum quantity for tier 1 pricing',
            ],
            'tier_1_price' => [
                'sample'      => '99.99',
                'description' => 'Price for tier 1 (1-10 units)',
            ],
            'tier_2_min_qty' => [
                'sample'      => '11',
                'description' => 'Minimum quantity for tier 2 pricing',
            ],
            'tier_2_max_qty' => [
                'sample'      => '50',
                'description' => 'Maximum quantity for tier 2 pricing',
            ],
            'tier_2_price' => [
                'sample'      => '89.99',
                'description' => 'Price for tier 2 (11-50 units)',
            ],
            'tier_3_min_qty' => [
                'sample'      => '51',
                'description' => 'Minimum quantity for tier 3 pricing',
            ],
            'tier_3_max_qty' => [
                'sample'      => '999',
                'description' => 'Maximum quantity for tier 3 pricing (999 = unlimited)',
            ],
            'tier_3_price' => [
                'sample'      => '79.99',
                'description' => 'Price for tier 3 (51+ units)',
            ],
        ];

        return array_merge($fields, $tierFields);
    }

    protected function generateSampleValue(string $field, $rules): string
    {
        $samples = [
            'name'             => 'Professional Wireless Headphones',
            'description'      => 'High-quality wireless headphones with noise cancellation, 30-hour battery life, and premium sound quality. Perfect for professionals and music enthusiasts.',
            'sku'              => 'WH-PRO-'.rand(1000, 9999),
            'brand'            => 'TechPro',
            'model_number'     => 'TP-WH-2024-PRO',
            'slug'             => 'professional-wireless-headphones-'.rand(100, 999),
            'price'            => '199.99',
            'currency'         => 'USD',
            'hs_code'          => '8518.30.20',
            'origin'           => 'Egypt',
            'specifications'   => '{"color": "Black", "weight": "250g", "battery_life": "30 hours", "connectivity": "Bluetooth 5.0", "noise_cancellation": "Active"}',
            'dimensions'       => '{"length": 20, "width": 18, "height": 8, "unit": "cm"}',
            'is_active'        => '1',
            'is_approved'      => '0',
            'is_featured'      => '0',
            'sample_available' => 'true',
            'sample_price'     => '25.00',
        ];

        if (isset($samples[$field])) {
            return $samples[$field];
        }

        $ruleString = is_array($rules) ? implode('|', $rules) : $rules;

        if (str_contains($ruleString, 'boolean')) {
            return '1';
        }
        if (str_contains($ruleString, 'numeric') || str_contains($ruleString, 'integer')) {
            return rand(1, 100);
        }
        if (str_contains($ruleString, 'email')) {
            return 'contact@company.com';
        }
        if (str_contains($ruleString, 'date')) {
            return now()->format('Y-m-d');
        }
        if (str_contains($ruleString, 'json')) {
            return '{"key": "value"}';
        }

        return 'Sample '.ucfirst(str_replace('_', ' ', $field));
    }

    protected function generateDescription(string $field, $rules): string
    {
        $ruleString = is_array($rules) ? implode('|', $rules) : $rules;
        $description = ucfirst(str_replace('_', ' ', $field));

        if (str_contains($ruleString, 'required')) {
            $description .= ' (Required)';
        }
        if (preg_match('/max:(\d+)/', $ruleString, $matches)) {
            $description .= " (Max: {$matches[1]} chars)";
        }
        if (str_contains($ruleString, 'unique')) {
            $description .= ' (Must be unique)';
        }
        if (str_contains($ruleString, 'json')) {
            $description .= ' (JSON format)';
        }

        return $description;
    }

    public function styles(Worksheet $sheet)
    {
        return [
            1 => [
                'font' => [
                    'bold'  => true,
                    'color' => ['rgb' => 'FFFFFF'],
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => '4472C4'],
                ],
            ],
            2 => [
                'font' => [
                    'italic' => true,
                    'color'  => ['rgb' => '666666'],
                    'size'   => 9,
                ],
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'F8F9FA'],
                ],
            ],
            3 => [
                'fill' => [
                    'fillType'   => Fill::FILL_SOLID,
                    'startColor' => ['rgb' => 'E7F3FF'],
                ],
            ],
        ];
    }

    public function columnWidths(): array
    {
        $widths = [];
        $column = 'A';

        foreach (array_keys($this->fields) as $field) {
            $width = match ($field) {
                'name', 'description' => 30,
                'sku', 'brand', 'model_number' => 20,
                'specifications', 'dimensions' => 25,
                'category_name' => 15,
                'price', 'sample_price' => 12,
                'currency', 'origin' => 10,
                default => 15
            };

            $widths[$column] = $width;
            $column++;
        }

        return $widths;
    }
}
