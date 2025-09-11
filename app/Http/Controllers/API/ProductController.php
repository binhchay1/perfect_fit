<?php

namespace App\Http\Controllers\API;

use App\Enums\Utility;
use App\Http\Controllers\Controller;
use App\Http\Requests\ProductRequest;
use App\Repositories\ProductRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class ProductController extends Controller
{
    use ApiResponseTrait;

    private $productRepository;
    protected $utility;

    public function __construct(
        ProductRepository $productRepository,
        Utility $utility
    )
    {
        $this->productRepository = $productRepository;
        $this->utility = $utility;
    }

    /**
     * Save product main images
     */
    private function saveProductImages($images): array
    {
        $imagePaths = [];
        foreach ($images as $image) {
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('/images/upload/product/'), $imageName);
            $imagePaths[] = '/images/upload/product/' . $imageName;
        }
        return $imagePaths;
    }

    /**
     * Save color images
     */
    private function saveColorImages($images): array
    {
        $imagePaths = [];
        foreach ($images as $image) {
            $imageName = time() . '_' . uniqid() . '.' . $image->getClientOriginalExtension();
            $image->move(public_path('/images/upload/product/colors/'), $imageName);
            $imagePaths[] = '/images/upload/product/colors/' . $imageName;
        }
        return $imagePaths;
    }

    /**
     * Display a listing of products
     */
    public function index(): JsonResponse
    {
        try {
            $products = $this->productRepository->index();
            $listProducts = $this->utility->paginate($products, 15);
    
            return $this->successResponse($listProducts, 'Products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve products', $e->getMessage());
        }
    }

    /**
     * Get all products (including inactive) for admin
     */
    public function getAll(): JsonResponse
    {
        try {
            $products = $this->productRepository->getAll();
            $listProducts = $this->utility->paginate($products, 15);
            return $this->successResponse($listProducts, 'All products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve all products', $e->getMessage());
        }
    }

    /**
     * Store a newly created product
     */
    public function store(ProductRequest $request): JsonResponse
    {
        try {
            DB::beginTransaction();

            $data = $request->validated();

            // Generate slug if not provided
            if (empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle main product image uploads
            if ($request->hasFile('images')) {
                $imagePaths = $this->saveProductImages($request->file('images'));
                $data['images'] = json_encode($imagePaths);
            }

            $product = $this->productRepository->store($data);
            // Handle tags if provided
            if ($request->has('tags') && is_array($request->tags)) {
                foreach ($request->tags as $tagName) {
                    $product->tags()->create(['tag' => $tagName]);
                }
            }

            // Handle colors and sizes if provided
            if ($request->has('colors') && is_array($request->colors)) {
                foreach ($request->colors as $colorData) {
                    $colorImages = null;

                    // Handle color images upload
                    if (isset($colorData['images']) && is_array($colorData['images'])) {
                        $colorImages = $this->saveColorImages($colorData['images']);
                    }

                    $color = $product->colors()->create([
                        'color_name' => $colorData['color_name'],
                        'images' => $colorImages ? json_encode($colorImages) : null,
                    ]);

                    // Handle sizes for this color
                    if (isset($colorData['sizes']) && is_array($colorData['sizes'])) {
                        foreach ($colorData['sizes'] as $sizeData) {
                            $color->sizes()->create([
                                'size_name' => $sizeData['size_name'],
                                'quantity' => $sizeData['quantity'] ?? 0,
                                'sku' => \App\Models\ProductSize::generateSku($product->id, $colorData['color_name'], $sizeData['size_name']),
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            $product->load(['brand', 'tags', 'colors.sizes']);

            return $this->successResponse($product, 'Product created successfully', 201);
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to create product', $e->getMessage());
        }
    }

    /**
     * Display the specified product
     */
    public function show(string $id): JsonResponse
    {
        try {
            $product = $this->productRepository->show($id);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            return $this->successResponse($product, 'Product retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve product', $e->getMessage());
        }
    }

    /**
     * Get product by slug
     */
    public function showBySlug(string $slug): JsonResponse
    {
        try {
            $product = $this->productRepository->showBySlug($slug);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            return $this->successResponse($product, 'Product retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve product', $e->getMessage());
        }
    }

    /**
     * Update the specified product
     */
    public function update(ProductRequest $request, string $id): JsonResponse
    {
        try {
            $product = $this->productRepository->show($id);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            DB::beginTransaction();

            $data = $request->validated();

            // Generate slug if name changed and slug not provided
            if (isset($data['name']) && empty($data['slug'])) {
                $data['slug'] = Str::slug($data['name']);
            }

            // Handle main product image uploads
            if ($request->hasFile('images')) {
                $imagePaths = $this->saveProductImages($request->file('images'));
                $data['images'] = json_encode($imagePaths);
            }

            $this->productRepository->update($data, $id);

            // Handle tags if provided
            if ($request->has('tags') && is_array($request->tags)) {
                // Delete existing tags
                $product->tags()->delete();

                // Create new tags
                foreach ($request->tags as $tagName) {
                    $product->tags()->create(['tag' => $tagName]);
                }
            }

            // Handle colors and sizes if provided
            if ($request->has('colors') && is_array($request->colors)) {
                // Delete existing colors and sizes
                $product->colors()->delete();

                // Create new colors and sizes
                foreach ($request->colors as $colorData) {
                    $colorImages = null;

                    // Handle color images upload
                    if (isset($colorData['images']) && is_array($colorData['images'])) {
                        $colorImages = $this->saveColorImages($colorData['images']);
                    }

                    $color = $product->colors()->create([
                        'color_name' => $colorData['color_name'],
                        'images' => $colorImages ? json_encode($colorImages) : null,
                    ]);

                    // Handle sizes for this color
                    if (isset($colorData['sizes']) && is_array($colorData['sizes'])) {
                        foreach ($colorData['sizes'] as $sizeData) {
                            $color->sizes()->create([
                                'size_name' => $sizeData['size_name'],
                                'quantity' => $sizeData['quantity'] ?? 0,
                                'sku' => \App\Models\ProductSize::generateSku($product->id, $colorData['color_name'], $sizeData['size_name']),
                            ]);
                        }
                    }
                }
            }

            DB::commit();

            $product->refresh();
            $product->load(['brand', 'tags', 'colors.sizes']);

            return $this->successResponse($product, 'Product updated successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to update product', $e->getMessage());
        }
    }

    /**
     * Remove the specified product
     */
    public function destroy(string $id): JsonResponse
    {
        try {
            $product = $this->productRepository->show($id);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            DB::beginTransaction();

            // Delete associated tags first
            $product->tags()->delete();

            // Delete the product
            $this->productRepository->deleteProduct($id);

            DB::commit();

            return $this->successResponse(null, 'Product deleted successfully');
        } catch (\Exception $e) {
            DB::rollBack();
            return $this->serverErrorResponse('Failed to delete product', $e->getMessage());
        }
    }

    /**
     * Search products
     */
    public function search(Request $request): JsonResponse
    {
        try {
            $keyword = $request->input('keyword', '');

            if (empty($keyword)) {
                return $this->errorResponse('Keyword is required', 400);
            }

            $products = $this->productRepository->search($keyword);

            return $this->successResponse($products, 'Products search completed');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to search products', $e->getMessage());
        }
    }

    /**
     * Get products by brand
     */
    public function getByBrand($brandId): JsonResponse
    {
        try {
            $products = $this->productRepository->getByBrand($brandId);

            return $this->successResponse($products, 'Products by brand retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve products by brand', $e->getMessage());
        }
    }

    /**
     * Get products by gender
     */
    public function getByGender($gender): JsonResponse
    {
        try {
            $products = $this->productRepository->getByGender($gender);

            return $this->successResponse($products, "Products for {$gender} retrieved successfully");
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve products by gender', $e->getMessage());
        }
    }

    /**
     * Get products with filters
     */
    public function getWithFilters(Request $request): JsonResponse
    {
        try {
            $filters = $request->only(['brand_id', 'gender', 'product_type', 'min_price', 'max_price', 'search']);
            $products = $this->productRepository->getWithFilters($filters);

            return $this->successResponse($products, 'Filtered products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve filtered products', $e->getMessage());
        }
    }

    /**
     * Get featured products
     */
    public function getFeatured(Request $request): JsonResponse
    {
        try {
            $limit = $request->input('limit', 10);
            $products = $this->productRepository->getFeatured($limit);

            return $this->successResponse($products, 'Featured products retrieved successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to retrieve featured products', $e->getMessage());
        }
    }

    /**
     * Toggle product active status
     */
    public function toggleStatus(string $id): JsonResponse
    {
        try {
            $product = $this->productRepository->toggleStatus($id);

            if (!$product) {
                return $this->notFoundResponse('Product not found');
            }

            return $this->successResponse($product, 'Product status updated successfully');
        } catch (\Exception $e) {
            return $this->serverErrorResponse('Failed to update product status', $e->getMessage());
        }
    }
}
