<?php

namespace App\Http\Controllers\Api\v1;

use App\Exports\ProductTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\Product\BulkProductActionRequest;
use App\Http\Requests\Product\BulkProductImportRequest;
use App\Http\Requests\Product\ProductUpdateStatusRequest;
use App\Http\Requests\Product\StoreProductRequest;
use App\Http\Requests\Product\UpdateProductRequest;
use App\Http\Resources\Product\ProductDetailsResource;
use App\Http\Resources\Product\ProductResource;
use App\Models\Category;
use App\Models\Product;
use App\Models\User;
use App\Services\CategoryService;
use App\Services\QueryHandler;
use App\Traits\ApiResponse;
use App\Traits\BulkProductOwnership;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    use ApiResponse, BulkProductOwnership;
    protected $categoryService;

    public function __construct(CategoryService $categoryService)
    {
        $this->categoryService = $categoryService;
    }

    /**
     * Display a listing of the resource.
     *
     *
     * @response ProductResource[]
     *
     * @unauthenticated
     */
    public function index(Request $request): JsonResponse
    {
        $queryHandler = new QueryHandler($request);
        $perPage = (int) $request->get('size', 10);
        $user = Auth::user(); // Get authenticated user

        $query = $queryHandler
            ->setBaseQuery(
                Product::query()
                    ->with(['seller.company', 'category', 'tags', 'tiers'])
                    ->withWishlistStatus($user?->id)
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

        return $this->apiResponse(
            ProductResource::collection($query),
            'Products retrieved successfully.',
            200,
            [
                'totalPages'     => $query->lastPage(),
                'limit'          => $query->perPage(),
                'total'          => $query->total(),
                'has_more_pages' => $query->hasMorePages(),
            ]
        );
    }

    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();
        $validated['dimensions'] = json_decode($validated['dimensions'], true);
        $validated['price_tiers'] = json_decode($validated['price_tiers'], true);
        $validated['product_tags'] = json_decode($validated['product_tags'], true);

        $categoryData = $validated['category'] ?? null;
        if (is_string($categoryData)) {
            $categoryData = json_decode($categoryData, true);
            if (json_last_error() !== JSON_ERROR_NONE) {
                throw new \Exception('Invalid JSON format for category data.');
            }
        }
        // Process category
        $categoryId = $this->categoryService->resolveCategoryId(
            $validated['category_id'],
            $categoryData ?? null
        );

        // Remove category_id and price_tiers from validated data
        $productData = collect($validated)
            ->except(['category_id', 'category', 'price_tiers'])
            ->merge(['category_id' => $categoryId])
            ->toArray();
        $productData['seller_id'] = Auth::user()->id;
        $product = DB::transaction(function () use ($productData, $validated) {
            $product = Product::create($productData);

            // Create product tiers
            if (isset($validated['price_tiers'])) {
                $product->tiers()->createMany($validated['price_tiers']);
            }
            $product->syncTags($validated['product_tags'] ?? []);

            return $product;
        });

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $product
                ->addMedia($request->file('main_image'))
                ->usingName(Str::limit($product->name.' - '.Carbon::now()->format('Y-m-d_H-i-s')), 200)
                ->toMediaCollection('main_image');
        }

        // Handle multiple images upload
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $product
                    ->addMedia($image)
                    ->usingName(Str::limit($product->name.' - '.Carbon::now()->format('Y-m-d_H-i-s')), 200)
                    ->toMediaCollection('product_images');
            }
        }

        // Handle documents upload
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $product
                    ->addMedia($document)
                    ->usingName(Str::limit($product->name.' - '.Carbon::now()->format('Y-m-d_H-i-s')), 200)
                    ->toMediaCollection('product_documents');
            }
        }

        if ($request->hasFile('specifications')) {
            foreach ($request->file('specifications') as $document) {
                $product
                    ->addMedia($document)
                    ->usingName(Str::limit($product->name.' - '.Carbon::now()->format('Y-m-d_H-i-s')), 200)
                    ->toMediaCollection('product_specifications');
            }
        }

        return $this->apiResponse(
            ProductDetailsResource::make($product->load('tiers', 'seller.company', 'tags', 'media')),
            'Product created successfully.',
            201
        );
    }

    /**
     * Display the specified resource.
     *
     * @unauthenticated
     */
    public function show(string $slug): JsonResponse
    {
        try {
            $user = Auth::user();
            $product = Product::with(['seller.company', 'category', 'tiers', 'media', 'tags'])
                ->withWishlistStatus($user?->id) // Add wishlist status
                ->where('is_active', true)
                ->where('is_approved', true)
                ->where('slug', $slug)
                ->firstOrFail();
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors(
                'Product not found.',
                ['slug' => $slug],
                404,
            );
        }

        return $this->apiResponse(
            ProductDetailsResource::make($product),
            'Product retrieved successfully.',
            200
        );
    }

    /**
     * Update the specified resource in storage.
     *
     * @authenticated
     */
    public function update(UpdateProductRequest $request): JsonResponse
    {
        // Get the product from middleware (ownership already verified)
        $product = $request->get('product');
        $validated = $request->validated();
        $validated['dimensions'] = json_decode($validated['dimensions'], true);
        $validated['price_tiers'] = json_decode($validated['price_tiers'], true);
        $validated['product_tags'] = json_decode($validated['product_tags'], true);

        // Remove file fields from validated data as they're handled separately
        $productData = collect($validated)->except(['main_image', 'images', 'documents', 'price_tiers'])->toArray();

        $product->update($productData);

        // Handle product tiers if provided
        if (isset($validated['price_tiers'])) {
            $product->tiers()->delete();
            $product->tiers()->createMany($validated['price_tiers']);
        }
        // Handle Tags if provided
        if (isset($validated['product_tags'])) {
            $product->tags()->detach(); // Detach existing tags
            $product->syncTags($validated['product_tags']);
        }

        // Handle main image upload (replace existing)
        if ($request->hasFile('main_image')) {
            $product->clearMediaCollection('main_image');
            $product
                ->addMedia($request->file('main_image'))
                ->usingName(Str::limit($product->name.' - '.Carbon::now()->format('Y-m-d_H-i-s')), 200)
                ->toMediaCollection('main_image');
        }

        // Handle multiple images upload (add to existing)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $product
                    ->addMedia($image)
                    ->usingName(Str::limit($product->name.' - '.Carbon::now()->format('Y-m-d_H-i-s')), 200)
                    ->toMediaCollection('product_images');
            }
        }

        // Handle documents upload (add to existing)
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $product
                    ->addMedia($document)
                    ->usingName(Str::limit($product->name.' - '.Carbon::now()->format('Y-m-d_H-i-s')), 200)
                    ->toMediaCollection('product_documents');
            }
        }

        if ($request->hasFile('specifications')) {
            foreach ($request->file('specifications') as $document) {
                $product
                    ->addMedia($document)
                    ->usingName(Str::limit($product->name.' - '.Carbon::now()->format('Y-m-d_H-i-s')), 200)
                    ->toMediaCollection('product_specifications');
            }
        }

        return $this->apiResponse(
            new ProductDetailsResource($product->fresh()->load(['seller.company', 'media', 'tiers', 'tags'])),
            'Product updated successfully',
            200
        );
    }

    /**
     * Update status of the specified resource in storage
     *
     * @authenticated
     */
    public function updateStatus(ProductUpdateStatusRequest $request, int $id): JsonResponse
    {
        // Get the product (ownership already verified in middleware or double-checking here)
        try {
            $product = Product::findOrFail($id);
            $data = $request->validated();
            $product->update($data);

            return $this->apiResponse(
                $product->fresh(),
                'Product status updated successfully',
                200
            );
        } catch (ModelNotFoundException $e) {
            return $this->apiResponseErrors(
                message: 'Product not found.',
                errors: ['id' => $id],
                status: 404,
            );
        }
    }

    /**
     * Remove the specified resource from storage.
     *
     * @authenticated
     */
    public function destroy(Request $request): JsonResponse
    {
        // Get the product from middleware (ownership already verified)
        $product = $request->get('product');
        $product->delete();

        return $this->apiResponse(
            null,
            'Product deleted successfully',
            200
        );
    }

    /**
     * delete image to product
     *
     * @authenticated
     */
    //    public function deleteImage(string $product, int $mediaId): JsonResponse
    //    {
    //        // Get the product from middleware (ownership already verified)
    //        $product = Product::where('slug', $product)->firstOrFail();
    //
    //        // Find the media item
    //        $media = $product->getMedia('product_images')->find($mediaId);
    //
    //        if (! $media) {
    //            return $this->apiResponseErrors(
    //                'Image not found.',
    //                ['media_id' => $mediaId],
    //                404,
    //            );
    //        }
    //
    //        // Delete the media item
    //        $media->delete();
    //
    //        return $this->apiResponse(
    //            null,
    //            'Image deleted successfully.',
    //            200
    //        );
    //    }

    /**
     * delete the product document.
     *
     * @param string|int $identifier
     * @return Product|null
     */
    public function deleteProductMedia(string $product, string $collection, int $mediaId): JsonResponse
    {
        if ($collection == 'main_image') {
            return $this->apiResponseErrors(
                'You can not delete main image.',
                ['media_id' => $mediaId],
                404,
            );
        }
        // Get the product from middleware (ownership already verified)
        $product = Product::where('slug', $product)->firstOrFail();

        // Find the media item

        $media = $product->getMedia($collection)->find($mediaId);

        if (! $media) {
            return $this->apiResponseErrors(
                'Document not found.',
                ['media_id' => $mediaId],
                404,
            );
        }

        // Delete the media item
        $media->delete();

        return $this->apiResponse(
            null,
            'Document deleted successfully.',
            200
        );
    }

    /**
     * Bulk delete products.
     */
    public function bulkDelete(BulkProductActionRequest $request): JsonResponse
    {
        $productIds = $request->validated()['product_ids'];

        // Verify ownership
        $ownership = $this->verifyBulkOwnership($productIds, auth()->id());

        if (empty($ownership['authorized'])) {
            return $this->noProductsFoundResponse($productIds);
        }

        if ($ownership['has_unauthorized']) {
            return $this->ownershipErrorResponse('delete', $ownership['unauthorized'], $ownership['authorized']);
        }

        $deletedCount = Product::whereIn('id', $ownership['authorized'])->delete();

        return $this->apiResponse(
            null,
            "$deletedCount products deleted successfully.",
            200
        );
    }

    /**
     * Bulk deactivate products.
     */
    public function bulkDeactivate(BulkProductActionRequest $request): JsonResponse
    {
        $productIds = $request->validated()['product_ids'];

        // Verify ownership
        $ownership = $this->verifyBulkOwnership($productIds, auth()->id());

        if (empty($ownership['authorized'])) {
            return $this->noProductsFoundResponse($productIds);
        }

        if ($ownership['has_unauthorized']) {
            return $this->ownershipErrorResponse('deactivate', $ownership['unauthorized'], $ownership['authorized']);
        }

        $updatedCount = Product::whereIn('id', $ownership['authorized'])->update(['is_active' => false]);

        return $this->apiResponse(
            null,
            "$updatedCount products set to inactive successfully.",
            200
        );
    }

    /**
     * Bulk activate products.
     */
    public function bulkActive(BulkProductActionRequest $request): JsonResponse
    {
        $productIds = $request->validated()['product_ids'];

        // Verify ownership
        $ownership = $this->verifyBulkOwnership($productIds, auth()->id());

        if (empty($ownership['authorized'])) {
            return $this->noProductsFoundResponse($productIds);
        }

        if ($ownership['has_unauthorized']) {
            return $this->ownershipErrorResponse('activate', $ownership['unauthorized'], $ownership['authorized']);
        }

        $updatedCount = Product::whereIn('id', $ownership['authorized'])->update(['is_active' => true]);

        return $this->apiResponse(
            null,
            "$updatedCount products set to active successfully.",
            200
        );
    }

    public function bulkImport(BulkProductImportRequest $request): JsonResponse
    {
        $validated = $request->validated();
        $products = $validated['products'] ?? []; // Assuming products come in as an array

        $savedProducts = [];
        DB::beginTransaction();

        try {
            foreach ($products as $product) {
                // Handle category ID lookup if category name is provided
                try {
                    if (isset($product['category_id'])) {
                        $category = Category::findOrFail($product['category_id']);
                    }
                    $product['seller_id'] = Auth::id();
                } catch (ModelNotFoundException $e) {
                    DB::rollBack();

                    return $this->apiResponseErrors(
                        'Category not found.',
                        [
                            'category' => $product['category'] ?? null,
                            'product'  => $product,
                        ],

                        404
                    );
                }

                // Create product from validated data
                $productModel = Product::create($product);

                // Handle product tiers if provided
                if (isset($product['price_tiers'])) {
                    $productModel->tiers()->createMany($product['price_tiers']);
                }

                // Handle product tags if provided
                if (isset($product['product_tags'])) {
                    $productModel->syncTags($product['product_tags']);
                }

                // Handle main image upload
                if (isset($product['main_image']) && filter_var($product['main_image'], FILTER_VALIDATE_URL)) {
                    $productModel
                        ->addMediaFromUrl($product['main_image'])
                        ->usingName($productModel->name.' - '.basename($product['main_image']))
                        ->toMediaCollection('main_image');
                }

                // Handle multiple images upload
                if (isset($product['images']) && is_array($product['images'])) {
                    foreach ($product['images'] as $imageUrl) {
                        if (filter_var($imageUrl, FILTER_VALIDATE_URL)) {
                            $productModel
                                ->addMediaFromUrl(url: $imageUrl)
                                ->usingName($productModel->name.' - '.basename($imageUrl))
                                ->toMediaCollection('product_images');
                        }
                    }
                }

                // Handle documents upload
                if (isset($product['documents']) && is_array($product['documents'])) {
                    foreach ($product['documents'] as $documentUrl) {
                        if (filter_var($documentUrl, FILTER_VALIDATE_URL)) {
                            $productModel
                                ->addMediaFromUrl(url: $documentUrl)
                                ->usingName($productModel->name.' - '.basename($documentUrl))
                                ->toMediaCollection('product_documents');
                        }
                    }
                }
                if (isset($product['specifications']) && is_array($product['specifications'])) {
                    foreach ($product['specifications'] as $specificationUrl) {
                        if (filter_var($specificationUrl, FILTER_VALIDATE_URL)) {
                            $productModel
                                ->addMediaFromUrl($specificationUrl)
                                ->usingName($productModel->name.' - '.basename($specificationUrl))
                                ->toMediaCollection('product_specifications');
                        }
                    }
                }

                $savedProducts[] = $productModel->fresh()->load('media', 'tiers', 'seller.company');
            }

            DB::commit();

            return $this->apiResponse(
                ProductDetailsResource::collection($savedProducts),
                'Products imported successfully.',
                201
            );
        } catch (\Exception $e) {
            DB::rollBack();

            return $this->apiResponseErrors(
                'An error occurred while importing products.',
                ['error' => $e->getMessage()],
                500
            );
        }
    }

    /**
     * @throws Exception
     * @throws \PhpOffice\PhpSpreadsheet\Writer\Exception
     *
     * @unauthenticated
     */
    public function downloadTemplate(): BinaryFileResponse
    {
        $filename = 'product_import_template_'.now()->format('Y-m-d').'.xlsx';

        return Excel::download(new ProductTemplateExport, $filename);
    }

    /**
     * Display products for seller or admin.
     *
     * This method retrieves products associated with the authenticated seller
     * or allows admins to view products for a specific seller via seller_id parameter.
     *
     * @authenticated
     */
    public function sellerProducts(Request $request): JsonResponse
    {
        $queryHandler = new QueryHandler($request);
        $perPage = (int) $request->get('size', 10);
        $user = Auth::user();

        $sellerId = null;
        if ($user->hasRole('admin')) {
            $sellerId = $request->get('seller_id');
            if (! $sellerId) {
                return $this->apiResponse(
                    [],
                    'Admin must provide seller_id parameter to view seller products.',
                    400
                );
            }

            $sellerExists = User::where('id', $sellerId)
                ->whereHas('roles', function ($query) {
                    $query->where('name', 'seller');
                })
                ->exists();

            if (! $sellerExists) {
                return $this->apiResponse(
                    [],
                    'Seller not found.',
                    404
                );
            }
        } elseif ($user->hasRole('seller')) {
            $sellerId = $user->id;
        } else {
            return $this->apiResponse(
                [],
                'Unauthorized. Only sellers and admins can access seller products.',
                403
            );
        }

        // Base query for statistics
        $statsQuery = Product::query()->where('seller_id', $sellerId);

        // Calculate statistics
        $total_products = $statsQuery->clone()->count();
        $featured_products = $statsQuery->clone()->where('is_featured', true)->count();
        $approved_products = $statsQuery->clone()->where('is_approved', true)->count();
        $pending_approval = $statsQuery->clone()->where('is_approved', false)->count();
        $active_products = $statsQuery->clone()->where('is_active', true)->count();
        $inactive_products = $statsQuery->clone()->where('is_active', false)->count();

        // Main query for paginated results
        $query = $queryHandler
            ->setBaseQuery(
                Product::query()
                    ->with(['seller.company', 'category', 'tags', 'media'])
                    ->withWishlistStatus($user?->id)
                    ->where('seller_id', $sellerId)
            )
            ->setAllowedSorts([
                'weight',
                'created_at',
                'name',
                'brand',
                'currency',
                'is_active',
                'is_featured',
                'is_approved',
                'category.name',
                'in_wishlist',
            ])
            ->setAllowedFilters([
                'name',
                'brand',
                'model_number',
                'currency',
                'weight',
                'origin',
                'is_active',
                'is_approved',
                'is_featured',
                'created_at',
                'in_wishlist',
            ])
            ->apply()
            ->paginate($perPage)
            ->withQueryString();

        $paginationMeta = $this->getPaginationMeta($query);
        $statsMeta = [
            'total_products'   => $total_products,
            'featured'         => $featured_products,
            'approved'         => $approved_products,
            'pending_approval' => $pending_approval,
            'active'           => $active_products,
            'inactive'         => $inactive_products,
        ];

        return $this->apiResponse(
            ProductResource::collection($query),
            'Seller products retrieved successfully.',
            200,
            array_merge($paginationMeta, $statsMeta)
        );
    }

    //    public function getTags(Request $request): JsonResponse
    //    {
    //        $tags = Tag::all();
    //
    //        return $this->apiResponse(
    //            TagResource::collection($tags),
    //            'Tags retrieved successfully.',
    //            200
    //        );
    //    }
}
