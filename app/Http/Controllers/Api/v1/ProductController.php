<?php

namespace App\Http\Controllers\Api\v1;

use App\Http\Controllers\Controller;
use App\Http\Requests\StoreProductRequest;
use App\Http\Requests\UpdateProductRequest;
use App\Http\Resources\ProductDetailsResource;
use App\Http\Resources\ProductResource;
use App\Models\Product;
use App\Traits\ApiResponse;
use Illuminate\Database\Eloquent\ModelNotFoundException;
use Illuminate\Http\JsonResponse;

class ProductController extends Controller
{
    use ApiResponse;

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
        $productData = collect($validated)->except(['main_image', 'images', 'documents'])->toArray();

        $product = Product::create($productData);

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
            ProductResource::make($product->load('media')),
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
     */
    public function update(UpdateProductRequest $request, string $id)
    {
        $product = Product::findOrFail($id);
        $validated = $request->validated();

        // Remove file fields from validated data as they're handled separately
        $productData = collect($validated)->except(['main_image', 'images', 'documents'])->toArray();

        $product->update($productData);

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
            ProductResource::make($product->load('media')),
            'Product updated successfully.',
            200
        );
    }

    /**
     * Remove the specified resource from storage.
     *
     * @unauthenticated
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = Product::findOrFail($id);
            $product->delete();

            return response()->json(['message' => 'Product deleted successfully.'], 204);
        } catch (ModelNotFoundException $e) {
            return response()->json(['message' => 'Product not found.'], 404);
        } catch (\Exception $e) {
            return response()->json(['message' => $e->getMessage()], 500);
        }
    }
}
