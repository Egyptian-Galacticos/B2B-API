<?php

namespace App\Http\Controllers\Api\v1;

use App\Exports\ProductTemplateExport;
use App\Http\Controllers\Controller;
use App\Http\Requests\BulkProductActionRequest;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductDetailsResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponse;
use App\Traits\BulkProductOwnership;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Maatwebsite\Excel\Facades\Excel;
use PhpOffice\PhpSpreadsheet\Exception;
use Symfony\Component\HttpFoundation\BinaryFileResponse;

class ProductController extends Controller
{
    use ApiResponse, BulkProductOwnership;

    /**
     * Display a listing of the resource.
     *
     *
     * @response ProductResource[]
     *
     * @unauthenticated
     */
    public function index(): JsonResponse
    {
        $products = Product::with(['seller.company', 'category'])
            ->where('is_active', true)
            ->paginate(10);

        return $this->apiResponse(
            ProductResource::collection($products),
            'Products retrieved successfully.',
            200
        );
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(StoreProductRequest $request)
    {
        $validated = $request->validated();

        // Remove file fields from validated data as they're handled separately
        $productData = collect($validated)->except(['main_image', 'images', 'documents', 'product_tires'])->toArray();

        $product = Product::create($productData);

        $product->tiers()->createMany($validated['product_tires']);

        // Handle main image upload
        if ($request->hasFile('main_image')) {
            $product
                ->addMedia($request->file('main_image'))
                ->usingName('Main Product Image - '.$request->file('main_image')->getClientOriginalName())
                ->toMediaCollection('main_image');
        }

        // Handle multiple images upload
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $product
                    ->addMedia($image)
                    ->usingName('Product Image - '.$image->getClientOriginalName())
                    ->toMediaCollection('product_images');
            }
        }

        // Handle documents upload
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $product
                    ->addMedia($document)
                    ->usingName('Product Document - '.$document->getClientOriginalName())
                    ->toMediaCollection('product_documents');
            }
        }

        return $this->apiResponse(
            ProductDetailsResource::make($product->load('media', 'tiers', 'seller.company')),
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
            $product = Product::with(['seller.company', 'category', 'tiers', 'media'])
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

        // Remove file fields from validated data as they're handled separately
        $productData = collect($validated)->except(['main_image', 'images', 'documents', 'product_tires'])->toArray();

        $product->update($productData);

        // Handle product tiers if provided
        if (isset($validated['product_tires'])) {
            $product->tiers()->delete();
            $product->tiers()->createMany($validated['product_tires']);
        }

        // Handle main image upload (replace existing)
        if ($request->hasFile('main_image')) {
            $product->clearMediaCollection('main_image');
            $product
                ->addMedia($request->file('main_image'))
                ->usingName('Main Product Image - '.$request->file('main_image')->getClientOriginalName())
                ->toMediaCollection('main_image');
        }

        // Handle multiple images upload (add to existing)
        if ($request->hasFile('images')) {
            foreach ($request->file('images') as $image) {
                $product
                    ->addMedia($image)
                    ->usingName('Product Image - '.$image->getClientOriginalName())
                    ->toMediaCollection('product_images');
            }
        }

        // Handle documents upload (add to existing)
        if ($request->hasFile('documents')) {
            foreach ($request->file('documents') as $document) {
                $product
                    ->addMedia($document)
                    ->usingName('Product Document - '.$document->getClientOriginalName())
                    ->toMediaCollection('product_documents');
            }
        }

        return $this->apiResponse(
            new ProductResource($product->fresh()->load(['seller.company', 'media', 'tiers'])),
            'Product updated successfully',
            200
        );
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
    public function deleteImage(string $product, int $mediaId): JsonResponse
    {
        // Get the product from middleware (ownership already verified)
        $product = Product::where('slug', $product)->firstOrFail();

        // Find the media item
        $media = $product->getMedia('product_images')->find($mediaId);

        if (! $media) {
            return $this->apiResponseErrors(
                'Image not found.',
                ['media_id' => $mediaId],
                404,
            );
        }

        // Delete the media item
        $media->delete();

        return $this->apiResponse(
            null,
            'Image deleted successfully.',
            200
        );
    }

    /**
     * delete the product document.
     *
     * @param string|int $identifier
     * @return Product|null
     */
    public function deleteDocument(string $product, int $mediaId): JsonResponse
    {
        // Get the product from middleware (ownership already verified)
        $product = Product::where('slug', $product)->firstOrFail();

        // Find the media item
        $media = $product->getMedia('product_documents')->find($mediaId);

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
}
