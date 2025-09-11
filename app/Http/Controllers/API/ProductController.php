<?php

namespace App\Http\Controllers\API;

use App\Enums\Utility;
use App\Http\Controllers\Controller;
use App\Repositories\ProductRepository;
use App\Traits\ApiResponseTrait;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;

class ProductController extends Controller
{
    use ApiResponseTrait;

    private $productRepository;
    protected $utility;

    public function __construct(
        ProductRepository $productRepository,
        Utility $utility
    ) {
        $this->productRepository = $productRepository;
        $this->utility = $utility;
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
}
