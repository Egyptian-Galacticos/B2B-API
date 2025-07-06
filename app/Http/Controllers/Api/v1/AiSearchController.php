<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Resources\Product\ProductResource;
use App\Models\Product;
use App\Services\QueryHandler;
use App\Traits\ApiResponse;
use Exception;
use Gemini\Laravel\Facades\Gemini;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;

class AiSearchController extends Controller
{
    use ApiResponse;

    /**
     * Perform an AI-powered search for products, with additional filtering and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'query' => 'required|string|max:255',
        ]);
        $searchQuery = $validated['query'];

        try {
            // Specify only the tables you want the AI to know about for context
            $relevantTables = ['products', 'categories', 'price_tiers'];
            $databaseStructure = [];

            foreach ($relevantTables as $tableName) {
                if (DB::getSchemaBuilder()->hasTable($tableName)) {
                    $columns = DB::getSchemaBuilder()->getColumnListing($tableName);
                    $columnDetails = [];

                    foreach ($columns as $column) {
                        $columnType = DB::getSchemaBuilder()->getColumnType($tableName, $column);
                        $columnDetails[] = (object) [
                            'Field' => $column,
                            'Type'  => $columnType,
                        ];
                    }

                    $databaseStructure[$tableName] = $columnDetails;
                }
            }

            if (empty($databaseStructure)) {
                return $this->apiResponseErrors(
                    message: 'No relevant product tables found in the database.',

                );
            }

            // Convert to readable format
            $structureText = "Database Structure:\n\n";
            foreach ($databaseStructure as $tableName => $columns) {
                $structureText .= "Table: {$tableName}\n";
                foreach ($columns as $column) {
                    $structureText .= "- {$column->Field} ({$column->Type})\n";
                }
                $structureText .= "\n";
            }

            $prompt = $structureText."\n\nRules:\n".
                "1. Return ONLY the SQL query to select the `id` column from the 'products' table.\n".
                "2. The query should find products based on the user's request.\n".
                "3. No markdown formatting or code blocks.\n".
                "4. Use MySQL syntax.\n".
                "5. Use CURDATE() for today's date if needed.\n".
                "6. When filtering by price, use proper JOIN with price_tiers table.\n".
                "7. Use DISTINCT when joining with price_tiers to avoid duplicates.\n".
                "8. Use table aliases for better readability (p for products, pt for price_tiers, c for categories).\n\n".
                "Example queries:\n".
                "- Text search: SELECT id FROM products WHERE name LIKE '%laptop%' OR description LIKE '%laptop%'\n".
                "- Brand search: SELECT id FROM products WHERE brand LIKE '%Apple%'\n".
                "- Price search: SELECT DISTINCT p.id FROM products p JOIN price_tiers pt ON p.id = pt.product_id WHERE pt.price < 100\n".
                "- Category search: SELECT id FROM products WHERE category_id = 1\n".
                "- Weight search: SELECT id FROM products WHERE weight < 5\n".
                "- Origin search: SELECT id FROM products WHERE origin = 'Germany'\n".
                "- Status search: SELECT id FROM products WHERE is_active = 1 AND is_approved = 1\n".
                "- Featured search: SELECT id FROM products WHERE is_featured = 1\n".
                "- Date search: SELECT id FROM products WHERE created_at >= CURDATE() - INTERVAL 7 DAY\n".
                "- Complex search: SELECT DISTINCT p.id FROM products p JOIN price_tiers pt ON p.id = pt.product_id WHERE p.brand LIKE '%Apple%' AND pt.price < 500\n\n".
                "User Request: \"$searchQuery\"";

            $queryResponse = Gemini::generativeModel('gemini-1.5-flash')
                ->generateContent($prompt);

            $cleanSql = trim($queryResponse->text());

            if (! preg_match('/^SELECT\s+(DISTINCT\s+)?.*\bid\b.*\s+FROM\s+.*products/i', $cleanSql)) {
                return $this->apiResponseErrors(
                    message: 'The AI have an error while search',
                );
            }

            // Execute the AI query to get the initial list of matching product IDs
            $rawResults = DB::select($cleanSql);
            $productIds = array_column($rawResults, 'id');

            // If the AI search returns no results, we can return an empty set immediately.
            if (empty($productIds)) {
                return $this->apiResponse(
                    data: [],
                    message: 'No products found.',
                );
            }

            $queryHandler = new QueryHandler($request);
            $perPage = (int) $request->get('size', 10);

            // Set the base query to only include products found by the AI
            $baseQuery = Product::query()
                ->with(['seller.company', 'category', 'tags', 'tiers']) // Eager load relationships
                ->whereIn('id', $productIds);

            // Apply all the allowed sorts and filters from your ProductController
            $paginatedQuery = $queryHandler
                ->setBaseQuery($baseQuery)
                ->setAllowedSorts([
                    'weight', 'created_at', 'name', 'brand', 'currency', 'is_active',
                    'seller.name', 'is_approved', 'is_featured',
                ])
                ->setAllowedFilters([
                    'name', 'brand', 'model_number', 'currency', 'weight', 'origin',
                    'is_active', 'is_approved', 'created_at', 'category.name',
                    'category.id', 'seller.company.name', 'seller_id', 'is_featured',
                ])
                ->apply()
                ->paginate($perPage)
                ->withQueryString();

            return $this->apiResponse(
                data: ProductResource::collection($paginatedQuery),
                message: 'Products found.',
                meta: $this->getPaginationMeta($paginatedQuery),
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                message: 'error on the server',
                errors: $e->getMessage()
            );
        }
    }
}
