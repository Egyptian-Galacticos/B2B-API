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
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Schema;

class AiSearchController extends Controller
{
    use ApiResponse;

    /**
     * Perform an AI-powered search for products, with additional filtering and sorting.
     */
    public function index(Request $request): JsonResponse
    {
        $validated = $request->validate([
            'search' => 'required|string|max:255',
        ]);
        $searchQuery = $validated['search'];

        // Log the search query for debugging
        Log::info('AI Search Query', ['query' => $searchQuery]);

        try {
            // Specify only the tables you want the AI to know about for context
            $relevantTables = ['products', 'categories', 'price_tiers'];
            $databaseStructure = [];

            foreach ($relevantTables as $tableName) {
                if (Schema::hasTable($tableName)) {
                    $columns = Schema::getColumnListing($tableName);
                    $columnDetails = [];
                    foreach ($columns as $column) {
                        $columnDetails[] = (object) [
                            'Field' => $column,
                            'Type'  => Schema::getColumnType($tableName, $column),
                        ];
                    }
                    $databaseStructure[$tableName] = $columnDetails;
                }
            }

            if (empty($databaseStructure)) {
                return $this->apiResponseErrors('No relevant product tables found in the database.');
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

            // 👇 *** SIMPLIFIED PROMPT: Use LIKE searches instead of Full-Text Search
            $prompt = $structureText."\n\nRules:\n".
                "1. You MUST return ONLY the SQL query, with no explanations or markdown.\n".
                "2. The query MUST select only the `id` column from the products table.\n".
                "3. If you cannot determine any filters, return: SELECT id FROM products WHERE 1=0\n".
                "4. For text searches, use LIKE with wildcards (%word%) for each keyword.\n".
                "5. Split search terms by spaces and search each word separately with OR.\n".
                "6. Use LEFT JOIN on categories table to include category names.\n".
                "7. Use table aliases: p for products, c for categories.\n".
                "8. Use DISTINCT when joining tables.\n\n".
                "Example queries:\n".
                "- Text search 'smart phone': SELECT DISTINCT p.id FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE (p.name LIKE '%smart%' OR p.description LIKE '%smart%' OR c.name LIKE '%smart%') AND (p.name LIKE '%phone%' OR p.description LIKE '%phone%' OR c.name LIKE '%phone%')\n".
                "- Single word 'laptop': SELECT DISTINCT p.id FROM products p LEFT JOIN categories c ON p.category_id = c.id WHERE p.name LIKE '%laptop%' OR p.description LIKE '%laptop%' OR c.name LIKE '%laptop%'\n".
                "- Brand search 'Samsung phone': SELECT DISTINCT p.id FROM products p WHERE p.brand LIKE '%Samsung%' AND (p.name LIKE '%phone%' OR p.description LIKE '%phone%')\n".
                "- Price with text: SELECT DISTINCT p.id FROM products p JOIN price_tiers pt ON p.id = pt.product_id WHERE pt.price BETWEEN 100 AND 500 AND (p.name LIKE '%electronics%' OR p.description LIKE '%electronics%')\n\n".
                "User Request: \"$searchQuery\"";

            $queryResponse = Gemini::generativeModel('gemini-1.5-flash')
                ->generateContent($prompt);

            $rawResponseText = $queryResponse->text();

            // Strip markdown code block if present
            $cleanSql = preg_replace('/^```sql\s*|\s*```$/', '', $rawResponseText);
            $cleanSql = trim($cleanSql);

            // Validation: Check for valid SQL query format
            if (
                ! preg_match('/^SELECT\s+(DISTINCT\s+)?p\.id\b.*\s+FROM\s+products\s+p/i', $cleanSql) &&
                ! preg_match('/^SELECT\s+(DISTINCT\s+)?id\b.*\s+FROM\s+products/i', $cleanSql)
            ) {
                Log::warning('Invalid AI query format', [
                    'user_query'   => $searchQuery,
                    'raw_response' => $rawResponseText,
                    'clean_sql'    => $cleanSql,
                ]);

                return $this->apiResponseErrors(
                    'The AI generated an invalid query format.',
                    errors: [
                        'query'   => $rawResponseText,
                        'cleaned' => $cleanSql,
                    ]
                );
            }

            // Safety check: Ensure query has filtering
            if (! str_contains(strtoupper($cleanSql), 'WHERE')) {
                Log::warning('AI query missing WHERE clause', [
                    'user_query' => $searchQuery,
                    'sql'        => $cleanSql,
                ]);

                return $this->apiResponseErrors('The AI failed to apply search filters.');
            }

            // Execute the AI query to get the initial list of matching product IDs
            Log::info('AI Search Executing Query', ['sql' => $cleanSql]);

            try {
                $rawResults = DB::select($cleanSql);
                $productIds = array_column($rawResults, 'id');
            } catch (\Exception $dbException) {
                Log::error('Database query execution failed', [
                    'user_query' => $searchQuery,
                    'sql'        => $cleanSql,
                    'error'      => $dbException->getMessage(),
                ]);

                return $this->apiResponseErrors(
                    'Database query execution failed.',
                    errors: [
                        'sql_error'     => $dbException->getMessage(),
                        'generated_sql' => $cleanSql,
                    ]
                );
            }

            Log::info('AI Search Query Results', [
                'query'         => $searchQuery,
                'sql'           => $cleanSql,
                'results_count' => count($productIds),
            ]);

            if (empty($productIds)) {
                return $this->apiResponse(
                    data: [
                        'debug_info' => [
                            'query'          => $searchQuery,
                            'generated_sql'  => $cleanSql,
                            'total_products' => DB::table('products')->count(),
                        ],
                    ],
                    message: 'No products found matching your specific query.',
                );
            }

            $queryHandler = new QueryHandler($request);
            $perPage = (int) $request->get('size', 10);

            $baseQuery = Product::query()
                ->with(['seller.company', 'category', 'tags', 'tiers'])
                ->whereIn('id', $productIds);

            // Add ordering based on the order returned by the AI query
            if (! empty($productIds)) {
                $orderedIds = implode(',', $productIds);
                // NOTE: FIELD() ensures our Eloquent results respect the order from the AI query
                $baseQuery->orderByRaw("FIELD(id, $orderedIds)");
            }

            $query = $queryHandler
                ->setBaseQuery(
                    Product::query()
                        ->with(['seller.company', 'category', 'tags', 'tiers'])
                        ->withWishlistStatus(auth()->id())
                        ->where('is_active', true)
                        ->where('is_approved', true)
                )
                ->setAllowedSorts([
                    'weight',
                    'created_at',
                    'name',
                    'brand',
                    'currency',
                    'is_active',
                    'seller.name',
                    'category.name',
                    'is_approved',
                    'is_featured',
                    'created_at',
                    'in_wishlist', // Add wishlist sorting
                ])
                ->setAllowedFilters([
                    'name',
                    'brand',
                    'model_number',
                    'currency',
                    'weight',
                    'origin',
                    'is_active',
                    'price',
                    'is_approved',
                    'created_at',
                    'category.name',
                    'category.id',
                    'seller.company.name',
                    'seller_id',
                    'is_featured',
                    'sample_available',
                    'in_wishlist', // Add wishlist filtering
                ])
                ->setSearchableFields([
                    'name',
                    'brand',
                    'description',
                    'model_number',
                ])
                ->apply()
                ->paginate($perPage)
                ->withQueryString();

            // Assuming you have a getPaginationMeta method in your ApiResponse trait
            $meta = method_exists($this, 'getPaginationMeta') ? $this->getPaginationMeta($query) : [];
            $meta['ai_query'] = $cleanSql; // Add the AI query to the meta for debugging

            return $this->apiResponse(
                data: ProductResource::collection($query),
                message: 'Products found.',
                meta: $meta
            );

        } catch (Exception $e) {
            return $this->apiResponseErrors(
                message: 'An error occurred on the server.',
                errors: $e->getMessage()
            );
        }
    }
}
